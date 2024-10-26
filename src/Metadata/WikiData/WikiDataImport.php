<?php
/**
 * WikiDataImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\WikiData;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Exception;

class WikiDataImport
{
    /**
     * Load book info from a WikiData entity
     *
     * @param string $basePath base directory
     * @param array<mixed> $entity WikiData entity
     * @param WikiDataCache|null $cache
     * @throws Exception if error
     *
     * @return BookInfo
     */
    public static function load($basePath, $entity, $cache = null)
    {
        if (empty($cache)) {
            if (basename($basePath) == 'wikidata') {
                $cacheDir = dirname($basePath);
            } else {
                $cacheDir = dirname(__DIR__, 3) . '/cache';
            }
            $cache = new WikiDataCache($cacheDir);
        }

        $bookInfo = new BookInfo();
        $bookInfo->source = 'wikidata';
        $bookInfo->basePath = $basePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfo->format = 'epub';
        $bookInfo->id = $entity['id'] ?? '';
        $bookInfo->uri = (string) WikiDataMatch::link($bookInfo->id);
        // @todo use calibre_external_storage in COPS
        $bookInfo->path = $bookInfo->uri;
        if (str_starts_with($bookInfo->path, $basePath)) {
            $bookInfo->path = substr($bookInfo->path, strlen($basePath) + 1);
        }
        $bookInfo->uuid = 'wd:' . $bookInfo->id;
        $bookInfo->title = $entity['label'] ?? '';
        $bookInfo->description = $entity['description'] ?? '';
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
            // author description available from cached author - not very interesting here
            $cacheFile = $cache->getEntity($authorId);
            if ($cache->hasCache($cacheFile)) {
                $author = array_merge($author, $cache->loadCache($cacheFile));
            }
            $description = $author['description'] ?? '';
            $info = [
                'id' => $authorId,
                'name' => $authorSort,
                'sort' => $authorName,
                'link' => WikiDataMatch::link($authorId),
                'description' => $description,
            ];
            $bookInfo->addAuthor($authorId, $info);
        }
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
        $idx = 0;
        foreach ($entities as $series) {
            $seriesId = $series['id'] ?? $idx;
            $title = $series['label'] ?? '';
            $index = $series['index'] ?? '';
            // series description available from cached series - not very interesting here
            $cacheFile = $cache->getEntity($seriesId);
            if ($cache->hasCache($cacheFile)) {
                $series = array_merge($series, $cache->loadCache($cacheFile));
            }
            $description = $series['description'] ?? '';
            $info = [
                'id' => $seriesId,
                'name' => $title,
                'sort' => SeriesInfo::getTitleSort($title),
                'index' => $index,
                'link' => WikiDataMatch::link($seriesId),
                'description' => $description,
            ];
            $bookInfo->addSeries($seriesId, $info);
            $idx++;
        }
        $bookInfo->identifiers = $entity['identifiers'] ?? ['wd' => $bookInfo->id];
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
        $bookInfo->timestamp = $bookInfo->creationDate;

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
