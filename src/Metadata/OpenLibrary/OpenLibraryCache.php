<?php
/**
 * OpenLibraryCache class
 */

namespace Marsender\EPubLoader\Metadata\OpenLibrary;

use Marsender\EPubLoader\Metadata\BaseCache;
use Marsender\EPubLoader\Metadata\OpenLibrary\Entities\AuthorEntity;
use Marsender\EPubLoader\Metadata\OpenLibrary\Entities\WorkEntity;
use Marsender\EPubLoader\Metadata\OpenLibrary\Search\AuthorSearchResult;
use Marsender\EPubLoader\Metadata\OpenLibrary\Search\WorkSearchResult;
use Exception;

class OpenLibraryCache extends BaseCache
{
    public const CACHE_TYPES = [
        'openlibrary/authors',
        'openlibrary/entities',
        'openlibrary/works/author',
        'openlibrary/works/title',
        //'openlibrary/editions',
        //'openlibrary/ratings',
    ];

    /**
     * Summary of getAuthorQuery
     * Path: '/openlibrary/authors/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getAuthorQuery($query, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/openlibrary/authors/' . $query . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorQueries
     * Path: '/openlibrary/authors/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getAuthorQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/openlibrary/authors/';
        return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getAuthorWork
     * Path: '/openlibrary/works/author/' . $authorId . '.' . $lang . '.' . $limit . '.json'
     * @param string $authorId
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 100)
     * @return string
     */
    public function getAuthorWork($authorId, $lang = 'en', $limit = 100)
    {
        $cacheFile = $this->cacheDir . '/openlibrary/works/author/' . $authorId . '.' . $lang . '.' . $limit . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorWorkIds
     * Path: '/openlibrary/works/author/'
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 100)
     * @return array<string>
     */
    public function getAuthorWorkIds($lang = 'en', $limit = 100)
    {
        $baseDir = $this->cacheDir . '/openlibrary/works/author/';
        return parent::getFiles($baseDir, '*.' . $lang . '.' . $limit . '.json', true);
    }

    /**
     * Summary of getTitleQuery
     * Path: '/openlibrary/works/title/' . $query . '.json'
     * @param string $query
     * @return string
     */
    public function getTitleQuery($query)
    {
        $cacheFile = $this->cacheDir . '/openlibrary/works/title/' . $query . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getTitleQueries
     * Path: '/openlibrary/works/title/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getTitleQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/openlibrary/works/title/';
        return parent::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of getAuthor
     * Path: '/openlibrary/entities/' . $authorId . '.' . $lang . '.json'
     * @param string $authorId
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getAuthor($authorId, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/openlibrary/entities/' . $authorId . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorIds
     * Path: '/openlibrary/entities/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getAuthorIds($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/openlibrary/entities/';
        // filter by *A and add it back
        return array_map(function ($id) {
            return $id . 'A';
        }, parent::getFiles($baseDir, '*A.' . $lang . '.json', true));
    }

    /**
     * Summary of getWork
     * Path: '/openlibrary/entities/' . $workId . '.' . $lang . '.json'
     * @param string $workId
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getWork($workId, $lang = 'en')
    {
        if (str_ends_with($workId, 'A')) {
            return $this->getAuthor($workId, $lang);
        }
        $cacheFile = $this->cacheDir . '/openlibrary/entities/' . $workId . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getWorkIds
     * Path: '/openlibrary/entities/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getWorkIds($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/openlibrary/entities/';
        // filter by *W and add it back
        return array_map(function ($id) {
            return $id . 'W';
        }, parent::getFiles($baseDir, '*W.' . $lang . '.json', true));
    }

    /**
     * Summary of getStats
     * @return array<mixed>
     */
    public function getStats()
    {
        return [
            'authors' => count($this->getAuthorQueries()),
            'works/title' => count($this->getTitleQueries()),
            'works/author' => count($this->getAuthorWorkIds()),
            'entities/A' => count($this->getAuthorIds()),
            'entities/W' => count($this->getWorkIds()),
        ];
    }

    /**
     * Summary of getEntries
     * @param string $cacheType
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>
     */
    public function getEntries($cacheType, $sort = null, $offset = null)
    {
        $offset ??= 0;
        $entries = match ($cacheType) {
            'authors' => $this->getAuthorQueries(),
            'works/title' => $this->getTitleQueries(),
            'works/author' => $this->getAuthorWorkIds(),
            'entities/A' => $this->getAuthorIds(),
            'entities/W' => $this->getWorkIds(),
            default => throw new Exception('Invalid cache type'),
        };
        // we will order & slice later for mtime or size - see BaseCache::getSortedEntries()
        if (empty($sort) || !in_array($sort, ['mtime', 'size'])) {
            $entries = array_slice($entries, $offset, static::$limit);
        }
        $result = [];
        foreach ($entries as $entry) {
            $cacheFile = match ($cacheType) {
                'authors' => $this->getAuthorQuery($entry),
                'works/title' => $this->getTitleQuery($entry),
                'works/author' => $this->getAuthorWork($entry),
                'entities/A' => $this->getAuthor($entry),
                'entities/W' => $this->getWork($entry),
                default => throw new Exception('Invalid cache type'),
            };
            $result[$entry] = [
                'id' => $entry,
                'file' => $cacheFile,
                'mtime' => filemtime($cacheFile),
                'size' => filesize($cacheFile),
            ];
        }
        // we order & slice here for mtime or size
        $result = static::getSortedEntries($result, $sort, $offset);
        return $result;
    }

    /**
     * Summary of getEntry
     * @param string $cacheType
     * @param string $cacheEntry
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function getEntry($cacheType, $cacheEntry, $urlPrefix = null)
    {
        $cacheFile = match ($cacheType) {
            'authors' => $this->getAuthorQuery($cacheEntry),
            'works/title' => $this->getTitleQuery($cacheEntry),
            'works/author' => $this->getAuthorWork($cacheEntry),
            'entities/A' => $this->getAuthor($cacheEntry),
            'entities/W' => $this->getWork($cacheEntry),
            default => throw new Exception('Invalid cache type'),
        };
        if ($this->hasCache($cacheFile)) {
            $entry = $this->loadCache($cacheFile);
            return match ($cacheType) {
                default => $entry,
            };
        }
        return null;
    }

    /**
     * Summary of parseAuthorSearch
     * @param array<mixed> $data
     * @return AuthorSearchResult
     */
    public static function parseAuthorSearch($data)
    {
        return AuthorSearchResult::fromJson($data);
    }

    /**
     * Summary of parseWorkSearch
     * @param array<mixed> $data
     * @return WorkSearchResult
     */
    public static function parseWorkSearch($data)
    {
        return WorkSearchResult::fromJson($data);
    }

    /**
     * Summary of parseAuthorEntity
     * @param array<mixed> $data
     * @return AuthorEntity
     */
    public static function parseAuthorEntity($data)
    {
        return AuthorEntity::fromJson($data);
    }

    /**
     * Summary of parseWorkEntity
     * @param array<mixed> $data
     * @return WorkEntity
     */
    public static function parseWorkEntity($data)
    {
        return WorkEntity::fromJson($data);
    }
}
