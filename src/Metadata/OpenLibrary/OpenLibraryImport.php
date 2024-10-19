<?php
/**
 * OpenLibraryImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\OpenLibrary;

use Marsender\EPubLoader\Metadata\BookInfos;
use Exception;

class OpenLibraryImport
{
    /**
     * Loads book infos from an OpenLibrary work
     *
     * @param string $inBasePath base directory
     * @param WorkEntity $work OpenLibrary work
     * @throws Exception if error
     *
     * @return BookInfos
     */
    public static function load($inBasePath, $work)
    {
        // @todo get from somewhere
        if (basename($inBasePath) == 'openlibrary') {
            $cacheDir = dirname($inBasePath);
        } else {
            $cacheDir = dirname(__DIR__, 3) . '/cache';
        }
        $match = new OpenLibraryMatch($cacheDir);

        $bookInfos = new BookInfos();
        $bookInfos->mSource = 'openlibrary';
        $bookInfos->mBasePath = $inBasePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfos->mFormat = 'epub';
        $bookInfos->mName = (string) str_replace('/works/', '', $work->getKey());
        // @todo use calibre_external_storage in COPS
        $bookInfos->mPath = (string) OpenLibraryMatch::link($bookInfos->mName);
        if (str_starts_with($bookInfos->mPath, $inBasePath)) {
            $bookInfos->mPath = substr($bookInfos->mPath, strlen($inBasePath) + 1);
        }
        $bookInfos->mUuid = 'olid:' . $bookInfos->mName;
        $bookInfos->mUri = (string) $bookInfos->mPath;
        $bookInfos->mTitle = (string) $work->getTitle();
        if (is_array($work->getDescription())) {
            $bookInfos->mDescription = (string) ($work->getDescription()['value'] ?? '');
        } else {
            $bookInfos->mDescription = (string) $work->getDescription();
        }
        $authors = [];
        $bookInfos->mAuthorIds = [];
        $entities = $work->getAuthors() ?? [];
        foreach ($entities as $author) {
            $authorId = (string) $author->getAuthor()?->getKey();
            if (empty($authorId)) {
                continue;
            }
            $authorId = str_replace('/authors/', '', $authorId);
            // lookup author info here
            $author = $match->getAuthor($authorId);
            if (empty($author['name'])) {
                continue;
            }
            $authorSort = BookInfos::getSortString($author['name']);
            $authors[$authorSort] = $author['name'];
            $bookInfos->mAuthorIds[] = $authorId;
        }
        $bookInfos->mAuthors = $authors;
        $subjects = [];
        $entities = $work->getSubjects() ?? [];
        foreach ($entities as $subject) {
            $subjects[] = (string) $subject;
        }
        $bookInfos->mSubjects = $subjects;
        if (!empty($work->getCovers())) {
            $covers = $work->getCovers();
            // pick the lowest cover number?
            sort($covers, SORT_NUMERIC);
            $cover = reset($covers);
            // @see https://openlibrary.org/dev/docs/api/covers
            $bookInfos->mCover = "https://covers.openlibrary.org/b/id/{$cover}-M.jpg";
        }
        // @todo ...
        //$bookInfos->mSerie = '...';

        $bookInfos->mCreationDate = (string) $work->getCreated()?->getValue();
        // @todo no modification date here
        $bookInfos->mModificationDate = (string) ($work->getLastModified()?->getValue() ?? $bookInfos->mCreationDate);
        // Timestamp is used to get latest ebooks
        $bookInfos->mTimeStamp = $bookInfos->mCreationDate;
        $bookInfos->mIdentifiers = ['olid' => $bookInfos->mName];

        return $bookInfos;
    }

    /**
     * Summary of getBookInfos
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfos
     */
    public static function getBookInfos($dbPath, $data)
    {
        $work = OpenLibraryCache::parseWorkEntity($data);
        return self::load($dbPath, $work);
    }
}
