<?php
/**
 * GoogleBooksCache class
 */

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

use Marsender\EPubLoader\Metadata\BaseCache;
use Marsender\EPubLoader\Metadata\GoogleBooks\Search\SearchResult;
use Marsender\EPubLoader\Metadata\GoogleBooks\Volumes\Volume;
use Exception;

class GoogleBooksCache extends BaseCache
{
    public const CACHE_TYPES = [
        'google/authors',
        'google/titles',
        'google/series',
        'google/volumes',
    ];

    /**
     * Summary of getAuthorQuery
     * Path: '/google/authors/' . $query . '.' . $lang . '.' . $limit . '.json'
     * @param string $query
     * @param ?string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 40)
     * @return string
     */
    public function getAuthorQuery($query, $lang = 'en', $limit = 40)
    {
        if (!empty($lang)) {
            return $this->cacheDir . '/google/authors/' . $query . '.' . $lang . '.' . $limit . '.json';
        }
        // $query includes .$lang
        return $this->cacheDir . '/google/authors/' . $query . '.' . $limit . '.json';
    }

    /**
     * Summary of getAuthorQueries
     * Path: '/google/authors/'
     * @param ?string $lang Language (default: null)
     * @param string|int $limit Max count of returning items (default: 40)
     * @return array<string>
     */
    public function getAuthorQueries($lang = null, $limit = 40)
    {
        $baseDir = $this->cacheDir . '/google/authors/';
        if (!empty($lang)) {
            return parent::getFiles($baseDir, '*.' . $lang . '.' . $limit . '.json', true);
        }
        return parent::getFiles($baseDir, '*.' . $limit . '.json', true);
    }

    /**
     * Summary of getTitleQuery
     * Path: '/google/titles/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param ?string $lang Language (default: en)
     * @return string
     */
    public function getTitleQuery($query, $lang = 'en')
    {
        if (!empty($lang)) {
            return $this->cacheDir . '/google/titles/' . $query . '.' . $lang . '.json';
        }
        // $query includes .$lang
        return $this->cacheDir . '/google/titles/' . $query . '.json';
    }

    /**
     * Summary of getTitleQueries
     * Path: '/google/titles/'
     * @param ?string $lang Language (default: null)
     * @return array<string>
     */
    public function getTitleQueries($lang = null)
    {
        $baseDir = $this->cacheDir . '/google/titles/';
        if (!empty($lang)) {
            return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
        }
        return parent::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of getSeriesQuery
     * Path: '/google/series/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param ?string $lang Language (default: en)
     * @return string
     */
    public function getSeriesQuery($query, $lang = 'en')
    {
        if (!empty($lang)) {
            return $this->cacheDir . '/google/series/' . $query . '.' . $lang . '.json';
        }
        // $query includes .$lang
        return $this->cacheDir . '/google/series/' . $query . '.json';
    }

    /**
     * Summary of getSeriesQueries
     * Path: '/google/series/'
     * @param ?string $lang Language (default: null)
     * @return array<string>
     */
    public function getSeriesQueries($lang = null)
    {
        $baseDir = $this->cacheDir . '/google/series/';
        if (!empty($lang)) {
            return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
        }
        return parent::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of getVolume
     * Path: '/google/volumes/' . $volumeId . '.' . $lang . '.json'
     * @param string $volumeId
     * @param ?string $lang Language (default: en)
     * @return string
     */
    public function getVolume($volumeId, $lang = 'en')
    {
        if (!empty($lang)) {
            return $this->cacheDir . '/google/volumes/' . $volumeId . '.' . $lang . '.json';
        }
        // $volumeId includes .$lang
        return $this->cacheDir . '/google/volumes/' . $volumeId . '.json';
    }

    /**
     * Summary of getVolumeIds
     * Path: '/google/volumes/'
     * @param ?string $lang Language (default: null)
     * @return array<string>
     */
    public function getVolumeIds($lang = null)
    {
        $baseDir = $this->cacheDir . '/google/volumes/';
        if (!empty($lang)) {
            return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
        }
        return parent::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of getStats
     * @param ?string $lang Language (default: null)
     * @return array<mixed>
     */
    public function getStats($lang = null)
    {
        return [
            'authors' => count($this->getAuthorQueries($lang)),
            'titles' => count($this->getTitleQueries($lang)),
            'series' => count($this->getSeriesQueries($lang)),
            'volumes' => count($this->getVolumeIds($lang)),
        ];
    }

    /**
     * Summary of getEntries
     * @param string $cacheType
     * @param string|null $sort
     * @param int|null $offset
     * @param ?string $lang Language (default: null)
     * @return array<mixed>
     */
    public function getEntries($cacheType, $sort = null, $offset = null, $lang = null)
    {
        $offset ??= 0;
        $entries = match ($cacheType) {
            'authors' => $this->getAuthorQueries($lang),
            'titles' => $this->getTitleQueries($lang),
            'series' => $this->getSeriesQueries($lang),
            'volumes' => $this->getVolumeIds($lang),
            default => throw new Exception('Invalid cache type'),
        };
        // we will order & slice later for mtime or size - see BaseCache::getSortedEntries()
        if (empty($sort) || !in_array($sort, ['mtime', 'size'])) {
            $entries = array_slice($entries, $offset, static::$limit);
        }
        $result = [];
        foreach ($entries as $entry) {
            $cacheFile = match ($cacheType) {
                'authors' => $this->getAuthorQuery($entry, $lang),
                'titles' => $this->getTitleQuery($entry, $lang),
                'series' => $this->getSeriesQuery($entry, $lang),
                'volumes' => $this->getVolume($entry, $lang),
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
     * @param ?string $lang Language (default: null)
     * @return array<mixed>|null
     */
    public function getEntry($cacheType, $cacheEntry, $urlPrefix = null, $lang = null)
    {
        $cacheFile = match ($cacheType) {
            'authors' => $this->getAuthorQuery($cacheEntry, $lang),
            'titles' => $this->getTitleQuery($cacheEntry, $lang),
            'series' => $this->getSeriesQuery($cacheEntry, $lang),
            'volumes' => $this->getVolume($cacheEntry, $lang),
            default => throw new Exception('Invalid cache type'),
        };
        if ($cacheType == 'titles' && !$this->hasCache($cacheFile)) {
            // try url-encoded for titles - see /meta/ with mikespub/php-epub-meta
            $cacheFile = $this->getTitleQuery(rawurlencode($cacheEntry));
        }
        if ($this->hasCache($cacheFile)) {
            $entry = $this->loadCache($cacheFile);
            // @todo pass along $lang extracted from $cacheEntry here?
            return match ($cacheType) {
                'authors' => $this->formatSearch($entry, $urlPrefix),
                'titles' => $this->formatSearch($entry, $urlPrefix),
                'series' => $this->formatSearch($entry, $urlPrefix),
                'volumes' => $this->formatVolume($entry, $urlPrefix),
                default => $entry,
            };
        }
        return null;
    }

    /**
     * Summary of formatSearch
     * @param array<mixed>|null $entry
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function formatSearch($entry, $urlPrefix)
    {
        if (empty($entry) || is_null($urlPrefix)) {
            return $entry;
        }
        $result = self::parseSearch($entry);
        foreach ($result->getItems() as $id => $volume) {
            $result->items[$id] = GoogleBooksImport::load($this->cacheDir . '/google', $volume);
            $volumeId = $result->items[$id]->id;
            // @todo language taken from search result here!?
            $lang = $result->items[$id]->language ?? 'en';
            $cacheFile = $this->getVolume($volumeId, $lang);
            if ($this->hasCache($cacheFile)) {
                $result->items[$id]->id = "<a href='{$urlPrefix}volumes?entry={$volumeId}.{$lang}'>{$volumeId}</a>";
            } else {
                $result->items[$id]->id = "<a href='{$urlPrefix}volumes?entry={$volumeId}.{$lang}'>{$volumeId}</a> ?";
            }
        }
        return (array) $result;
    }

    /**
     * Summary of formatVolume
     * @param array<mixed>|null $entry
     * @param string|null $urlPrefix
     * @param ?string $lang Language (default: null)
     * @return array<mixed>|null
     */
    public function formatVolume($entry, $urlPrefix, $lang = null)
    {
        if (is_null($entry) || is_null($urlPrefix)) {
            return $entry;
        }
        $lang ??= 'en';
        $volume = self::parseVolume($entry);
        $bookInfo = GoogleBooksImport::load($this->cacheDir . '/google', $volume);
        return (array) $bookInfo;
    }

    /**
     * Parse JSON data for Google Books search result
     *
     * @param array<mixed> $data
     *
     * @return SearchResult
     */
    public static function parseSearch($data)
    {
        $result = SearchResult::fromJson($data);
        return $result;
    }

    /**
     * Parse JSON data for a Google Books volume
     *
     * @param array<mixed> $data
     *
     * @return Volume
     */
    public static function parseVolume($data)
    {
        $volume = Volume::fromJson($data);
        return $volume;
    }
}
