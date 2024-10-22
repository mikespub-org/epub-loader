<?php
/**
 * WikiDataImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\WikiData;

use Marsender\EPubLoader\Metadata\BookInfos;
use Exception;

class WikiDataImport
{
    /**
     * Loads book infos from a WikiData entity
     *
     * @param string $inBasePath base directory
     * @param array<mixed> $entity WikiData entity
     * @throws Exception if error
     *
     * @return BookInfos
     */
    public static function load($inBasePath, $entity)
    {
        $bookInfos = new BookInfos();
        $bookInfos->mSource = 'wikidata';
        $bookInfos->mBasePath = $inBasePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfos->mFormat = 'epub';
        $bookInfos->mName = $entity['id'] ?? '';
        // @todo use calibre_external_storage in COPS
        $bookInfos->mPath = (string) WikiDataMatch::link($bookInfos->mName);
        if (str_starts_with($bookInfos->mPath, $inBasePath)) {
            $bookInfos->mPath = substr($bookInfos->mPath, strlen($inBasePath) + 1);
        }
        $bookInfos->mUuid = 'wd:' . $bookInfos->mName;
        $bookInfos->mUri = (string) $bookInfos->mPath;
        $bookInfos->mTitle = $entity['label'] ?? '';
        $bookInfos->mDescription = $entity['description'] ?? '';
        $authors = [];
        $bookInfos->mAuthorIds = [];
        $entities = $entity['author'] ?? [];
        foreach ($entities as $author) {
            $authorId = $author['id'] ?? '';
            if (empty($authorId)) {
                continue;
            }
            $authorName = $author['label'] ?? '';
            if (empty($authorName)) {
                continue;
            }
            $authorSort = BookInfos::getAuthorSort($authorName);
            $authors[$authorSort] = $authorName;
            $bookInfos->mAuthorIds[] = $authorId;
        }
        $bookInfos->mAuthors = $authors;
        $subjects = [];
        $entities = $entity['genre'] ?? [];
        foreach ($entities as $subject) {
            $subjects[] = $subject['label'] ?? '';
        }
        $bookInfos->mSubjects = $subjects;
        $bookInfos->mCover = $entity['cover'] ?? '';
        $bookInfos->mPublisher = $entity['publisher'] ?? '';
        $bookInfos->mLanguage = $entity['language'] ?? '';
        $entities = $entity['series'] ?? [];
        $bookInfos->mSerieIds = [];
        foreach ($entities as $series) {
            // use only the 1st series for name & index here
            if ($bookInfos->mSerieIndex == '') {
                $bookInfos->mSerieIndex = $series['index'] ?? '0';
            }
            if ($bookInfos->mSerie == '') {
                $bookInfos->mSerie = $series['label'] ?? '';
            }
            // save ids of the other series here for matching?
            $bookInfos->mSerieIds[] = $series['id'] ?? '';
        }
        $bookInfos->mIdentifiers = $entity['identifiers'] ?? ['wd' => $bookInfos->mName];
        foreach ($bookInfos->mIdentifiers as $type => $value) {
            if ($type == 'isbn') {
                $bookInfos->mIsbn = $value;
                break;
            }
        }
        // @todo ...

        $bookInfos->mCreationDate = $entity['published'] ?? '';
        // @todo no modification date here
        $bookInfos->mModificationDate = $bookInfos->mCreationDate;
        // Timestamp is used to get latest ebooks
        $bookInfos->mTimeStamp = $bookInfos->mCreationDate;

        return $bookInfos;
    }

    /**
     * Summary of getBookInfos
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfos|null
     */
    public static function getBookInfos($dbPath, $data)
    {
        $entity = WikiDataCache::parseEntity($data);
        if (empty($entity) || $entity['type'] != 'book') {
            return null;
        }
        return self::load($dbPath, $entity);
    }
}
