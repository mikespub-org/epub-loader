<?php
/**
 * GoogleBooksCache class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
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
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 40)
     * @return string
     */
    public function getAuthorQuery($query, $lang = 'en', $limit = 40)
    {
        $cacheFile = $this->cacheDir . '/google/authors/' . $query . '.' . $lang . '.' . $limit . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorQueries
     * Path: '/google/authors/'
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 40)
     * @return array<string>
     */
    public function getAuthorQueries($lang = 'en', $limit = 40)
    {
        $baseDir = $this->cacheDir . '/google/authors/';
        return parent::getFiles($baseDir, '*.' . $lang . '.' . $limit . '.json', true);
    }

    /**
     * Summary of getTitleQuery
     * Path: '/google/titles/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getTitleQuery($query, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/google/titles/' . $query . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getTitleQueries
     * Path: '/google/titles/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getTitleQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/google/titles/';
        return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getSeriesQuery
     * Path: '/google/series/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getSeriesQuery($query, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/google/series/' . $query . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getSeriesQueries
     * Path: '/google/series/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getSeriesQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/google/series/';
        return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getVolume
     * Path: '/google/volumes/' . $volumeId . '.' . $lang . '.json'
     * @param string $volumeId
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getVolume($volumeId, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/google/volumes/' . $volumeId . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getVolumeIds
     * Path: '/google/volumes/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getVolumeIds($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/google/volumes/';
        return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getStats
     * @return array<mixed>
     */
    public function getStats()
    {
        return [
            'authors' => count($this->getAuthorQueries()),
            'titles' => count($this->getTitleQueries()),
            'series' => count($this->getSeriesQueries()),
            'volumes' => count($this->getVolumeIds()),
        ];
    }

    /**
     * Summary of getEntries
     * @param string $cacheType
     * @param int|null $offset
     * @return array<mixed>
     */
    public function getEntries($cacheType, $offset = null)
    {
        $offset ??= 0;
        $entries = match ($cacheType) {
            'authors' => $this->getAuthorQueries(),
            'titles' => $this->getTitleQueries(),
            'series' => $this->getSeriesQueries(),
            'volumes' => $this->getVolumeIds(),
            default => throw new Exception('Invalid cache type'),
        };
        $entries = array_slice($entries, $offset, static::$limit);
        return $entries;
    }

    /**
     * Summary of getEntry
     * @param string $cacheType
     * @param string $cacheEntry
     * @return array<mixed>|null
     */
    public function getEntry($cacheType, $cacheEntry)
    {
        $cacheFile = match ($cacheType) {
            'authors' => $this->getAuthorQuery($cacheEntry),
            'titles' => $this->getTitleQuery($cacheEntry),
            'series' => $this->getSeriesQuery($cacheEntry),
            'volumes' => $this->getVolume($cacheEntry),
            default => throw new Exception('Invalid cache type'),
        };
        if ($this->hasCache($cacheFile)) {
            return $this->loadCache($cacheFile);
        }
        return null;
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
