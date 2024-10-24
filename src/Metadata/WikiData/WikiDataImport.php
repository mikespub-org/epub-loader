<?php
/**
 * WikiDataImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\WikiData;

use Marsender\EPubLoader\Metadata\AuthorInfo;
use Marsender\EPubLoader\Metadata\BookInfo;
use Marsender\EPubLoader\Metadata\SeriesInfo;
use Exception;

class WikiDataImport
{
    /**
     * Loads book infos from a WikiData entity
     *
     * @param string $basePath base directory
     * @param array<mixed> $entity WikiData entity
     * @throws Exception if error
     *
     * @return BookInfo
     */
    public static function load($basePath, $entity)
    {
        $bookInfo = new BookInfo();
        $bookInfo->source = 'wikidata';
        $bookInfo->basePath = $basePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfo->format = 'epub';
        $bookInfo->name = $entity['id'] ?? '';
        // @todo use calibre_external_storage in COPS
        $bookInfo->path = (string) WikiDataMatch::link($bookInfo->name);
        if (str_starts_with($bookInfo->path, $basePath)) {
            $bookInfo->path = substr($bookInfo->path, strlen($basePath) + 1);
        }
        $bookInfo->uuid = 'wd:' . $bookInfo->name;
        $bookInfo->uri = (string) $bookInfo->path;
        $bookInfo->title = $entity['label'] ?? '';
        $bookInfo->description = $entity['description'] ?? '';
        $authors = [];
        $bookInfo->authorIds = [];
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
            $authorSort = AuthorInfo::getNameSort($authorName);
            $authors[$authorSort] = $authorName;
            $bookInfo->authorIds[] = $authorId;
        }
        $bookInfo->authors = $authors;
        $subjects = [];
        $entities = $entity['genre'] ?? [];
        foreach ($entities as $subject) {
            $subjects[] = $subject['label'] ?? '';
        }
        $bookInfo->subjects = $subjects;
        $bookInfo->cover = $entity['cover'] ?? '';
        $bookInfo->publisher = $entity['publisher'] ?? '';
        $bookInfo->language = $entity['language'] ?? '';
        $entities = $entity['series'] ?? [];
        $bookInfo->serieIds = [];
        foreach ($entities as $series) {
            // use only the 1st series for name & index here
            if ($bookInfo->serieIndex == '') {
                $bookInfo->serieIndex = $series['index'] ?? '0';
            }
            if ($bookInfo->serie == '') {
                $bookInfo->serie = $series['label'] ?? '';
            }
            // save ids of the other series here for matching?
            $bookInfo->serieIds[] = $series['id'] ?? '';
        }
        $bookInfo->identifiers = $entity['identifiers'] ?? ['wd' => $bookInfo->name];
        foreach ($bookInfo->identifiers as $type => $value) {
            if ($type == 'isbn') {
                $bookInfo->isbn = $value;
                break;
            }
        }
        // @todo ...

        $bookInfo->creationDate = $entity['published'] ?? '';
        // @todo no modification date here
        $bookInfo->modificationDate = $bookInfo->creationDate;
        // Timestamp is used to get latest ebooks
        $bookInfo->timeStamp = $bookInfo->creationDate;

        return $bookInfo;
    }

    /**
     * Summary of getBookInfo
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfo|null
     */
    public static function getBookInfo($dbPath, $data)
    {
        $entity = WikiDataCache::parseEntity($data);
        if (empty($entity) || $entity['type'] != 'book') {
            return null;
        }
        return self::load($dbPath, $entity);
    }
}
