<?php
/**
 * WikiDataImport class
 */

namespace Marsender\EPubLoader\Metadata\WikiData;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Exception;

class WikiDataImport
{
    public const SOURCE = 'WikiData';

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
        if (empty($entity) || $entity['type'] != 'book') {
            throw new Exception('Wrong entity type to load BookInfo');
        }
        if (empty($cache)) {
            if (basename($basePath) == 'wikidata') {
                $cacheDir = dirname($basePath);
            } else {
                $cacheDir = dirname(__DIR__, 3) . '/cache';
            }
            $cache = new WikiDataCache($cacheDir);
        }

        $bookInfo = new BookInfo();
        $bookInfo->source = self::SOURCE;
        $bookInfo->basePath = $basePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfo->format = 'epub';
        $bookInfo->id = $entity['id'] ?? '';
        $bookInfo->uri = (string) WikiDataMatch::link($bookInfo->id);
        // @todo use calibre_external_storage in COPS
        $bookInfo->path = $bookInfo->uri;
        if (!empty($basePath) && str_starts_with($bookInfo->path, $basePath)) {
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
            // author description available from cached author - not very interesting here
            $cacheFile = $cache->getEntity($authorId);
            if ($cache->hasCache($cacheFile)) {
                $authorData = $cache->loadCache($cacheFile);
                $authorEntity = WikiDataCache::parseEntity($authorData);
                if (!empty($authorEntity)) {
                    $authorInfo = self::loadAuthor($basePath, $authorEntity, $cache);
                    $bookInfo->authors[$authorId] = $authorInfo;
                    continue;
                }
            }
            $authorName = $author['label'] ?? '';
            if (empty($authorName)) {
                continue;
            }
            $authorSort = AuthorInfo::getNameSort($authorName);
            $image = $author['cover'] ?? '';
            $description = $author['description'] ?? '';
            $info = [
                'id' => $authorId,
                'name' => $authorName,
                'sort' => $authorSort,
                'link' => WikiDataMatch::link($authorId),
                'image' => $image,
                'description' => $description,
                'source' => self::SOURCE,
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
            // series description available from cached series - not very interesting here
            $cacheFile = $cache->getEntity($seriesId);
            if ($cache->hasCache($cacheFile)) {
                $seriesData = $cache->loadCache($cacheFile);
                $seriesEntity = WikiDataCache::parseEntity($seriesData);
                if (!empty($seriesEntity)) {
                    try {
                        $seriesInfo = self::loadSeries($basePath, $seriesEntity, $cache);
                        $seriesInfo->index = $series['index'] ?? '';
                        $bookInfo->series[$seriesId] = $seriesInfo;
                        continue;
                    } catch (Exception) {
                        // ...
                    }
                }
            }
            $title = $series['label'] ?? '';
            $index = $series['index'] ?? '';
            $image = $series['cover'] ?? '';
            $description = $series['description'] ?? '';
            $info = [
                'id' => $seriesId,
                'name' => $title,
                'sort' => SeriesInfo::getTitleSort($title),
                'index' => $index,
                'link' => WikiDataMatch::link($seriesId),
                'image' => $image,
                'description' => $description,
                'source' => self::SOURCE,
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
     * Load author info from a WikiData entity
     *
     * @param string $basePath base directory
     * @param array<mixed> $entity WikiData entity
     * @param WikiDataCache|null $cache
     * @throws Exception if error
     *
     * @return AuthorInfo
     */
    public static function loadAuthor($basePath, $entity, $cache = null)
    {
        if (empty($entity) || $entity['type'] != 'author') {
            throw new Exception('Wrong entity type to load AuthorInfo');
        }
        if (empty($cache)) {
            if (basename($basePath) == 'wikidata') {
                $cacheDir = dirname($basePath);
            } else {
                $cacheDir = dirname(__DIR__, 3) . '/cache';
            }
            $cache = new WikiDataCache($cacheDir);
        }

        $authorInfo = new AuthorInfo();
        $authorInfo->source = self::SOURCE;
        $authorInfo->basePath = $basePath;
        $authorInfo->id = $entity['id'] ?? '';
        $authorInfo->name = $entity['label'] ?? '';
        $authorInfo->sort = AuthorInfo::getNameSort($authorInfo->name);
        $authorInfo->link = WikiDataMatch::link($authorInfo->id);
        $authorInfo->image =  $entity['cover'] ?? '';
        if (!empty($entity['description'])) {
            $authorInfo->addNote($entity['description']);
        }
        $authorInfo->identifiers = $entity['identifiers'] ?? ['wd' => $authorInfo->id];
        $entities = $entity['bookList'] ?? [];
        $idx = 0;
        $authors = [];
        foreach ($entities as $book) {
            $bookId = $book['id'] ?? $idx;
            $title = $book['label'] ?? '';
            $info = [
                'id' => $bookId,
                'title' => $title,
                'sort' => BookInfo::getTitleSort($title),
                'uri' => WikiDataMatch::link($bookId),
                'authors' => $authors,
                'source' => self::SOURCE,
            ];
            $authorInfo->addBook($bookId, $info);
            $idx++;
        }

        return $authorInfo;
    }

    /**
     * Load series info from a WikiData entity
     *
     * @param string $basePath base directory
     * @param array<mixed> $entity WikiData entity
     * @param WikiDataCache|null $cache
     * @throws Exception if error
     *
     * @return SeriesInfo
     */
    public static function loadSeries($basePath, $entity, $cache = null)
    {
        if (empty($entity) || $entity['type'] != 'series') {
            throw new Exception('Wrong entity type to load SeriesInfo');
        }
        if (empty($cache)) {
            if (basename($basePath) == 'wikidata') {
                $cacheDir = dirname($basePath);
            } else {
                $cacheDir = dirname(__DIR__, 3) . '/cache';
            }
            $cache = new WikiDataCache($cacheDir);
        }

        $seriesInfo = new SeriesInfo();
        $seriesInfo->source = self::SOURCE;
        $seriesInfo->basePath = $basePath;
        $seriesInfo->id = $entity['id'] ?? '';
        $seriesInfo->title = $entity['label'] ?? '';
        $seriesInfo->sort = SeriesInfo::getTitleSort($seriesInfo->title);
        $seriesInfo->link = WikiDataMatch::link($seriesInfo->id);
        $seriesInfo->image =  $entity['cover'] ?? '';
        if (!empty($entity['description'])) {
            $seriesInfo->addNote($entity['description']);
        }
        $seriesInfo->identifiers = $entity['identifiers'] ?? ['wd' => $seriesInfo->id];
        $entities = $entity['bookList'] ?? [];
        $idx = 0;
        foreach ($entities as $book) {
            $bookId = $book['id'] ?? $idx;
            $title = $book['label'] ?? '';
            $info = [
                'id' => $bookId,
                'title' => $title,
                'sort' => BookInfo::getTitleSort($title),
                'uri' => WikiDataMatch::link($bookId),
                //'series' => $seriesInfo->id,
                'source' => self::SOURCE,
            ];
            $seriesInfo->addBook($bookId, $info);
            $idx++;
        }
        if (!empty($entity['author'])) {
            // ...
        }

        return $seriesInfo;
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
