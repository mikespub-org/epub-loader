<?php
/**
 * WikiDataCache class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\WikiData;

use Marsender\EPubLoader\Metadata\BaseCache;
use Wikidata\Entity;
use Wikidata\SearchResult;
use Exception;

class WikiDataCache extends BaseCache
{
    public const CACHE_TYPES = [
        'wikidata/authors',
        'wikidata/works/author',
        'wikidata/works/name',
        'wikidata/works/title',
        'wikidata/series/author',
        'wikidata/series/title',
        'wikidata/entities',
    ];

    /**
     * Summary of getAuthorQuery
     * Path: '/wikidata/authors/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getAuthorQuery($query, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/wikidata/authors/' . $query . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorQueries
     * Path: '/wikidata/authors/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getAuthorQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/wikidata/authors/';
        return static::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getAuthorWork
     * Path: '/wikidata/works/author/' . $authorId . '.' . $lang . '.' . $limit . '.json'
     * @param string $authorId
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 10)
     * @return string
     */
    public function getAuthorWork($authorId, $lang = 'en', $limit = 100)
    {
        $cacheFile = $this->cacheDir . '/wikidata/works/author/' . $authorId . '.' . $lang . '.' . $limit . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorWorkIds
     * Path: '/wikidata/works/author/'
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 100)
     * @return array<string>
     */
    public function getAuthorWorkIds($lang = 'en', $limit = 100)
    {
        $baseDir = $this->cacheDir . '/wikidata/works/author/';
        return static::getFiles($baseDir, '*.' . $lang . '.' . $limit . '.json', true);
    }

    /**
     * Summary of getAuthorWorkQuery
     * Path: '/wikidata/works/name/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getAuthorWorkQuery($query, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/wikidata/works/name/' . $query . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorWorkQueries
     * Path: '/wikidata/works/name/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getAuthorWorkQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/wikidata/works/name/';
        return static::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getTitleQuery
     * Path: '/wikidata/works/title/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getTitleQuery($query, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/wikidata/works/title/' . $query . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getTitleQueries
     * Path: '/wikidata/works/title/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getTitleQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/wikidata/works/title/';
        return static::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getAuthorSeries
     * Path: '/wikidata/series/author/' . $authorId . '.' . $lang . '.' . $limit . '.json'
     * @param string $authorId
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 10)
     * @return string
     */
    public function getAuthorSeries($authorId, $lang = 'en', $limit = 100)
    {
        $cacheFile = $this->cacheDir . '/wikidata/series/author/' . $authorId . '.' . $lang . '.' . $limit . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorSeriesIds
     * Path: '/wikidata/series/author/'
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 100)
     * @return array<string>
     */
    public function getAuthorSeriesIds($lang = 'en', $limit = 100)
    {
        $baseDir = $this->cacheDir . '/wikidata/series/author/';
        return static::getFiles($baseDir, '*.' . $lang . '.' . $limit . '.json', true);
    }

    /**
     * Summary of getSeriesQuery
     * Path: '/wikidata/series/title/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getSeriesQuery($query, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/wikidata/series/title/' . $query . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getSeriesQueries
     * Path: '/wikidata/series/title/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getSeriesQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/wikidata/series/title/';
        return static::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getEntity
     * Path: '/wikidata/entities/' . $entityId . '.' . $lang . '.json'
     * @param string $entityId
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getEntity($entityId, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/wikidata/entities/' . $entityId . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getEntityIds
     * Path: '/wikidata/entities/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getEntityIds($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/wikidata/entities/';
        return static::getFiles($baseDir, '*.' . $lang . '.json', true);
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
            'works/name' => count($this->getAuthorWorkQueries()),
            'series/title' => count($this->getSeriesQueries()),
            'series/author' => count($this->getAuthorSeriesIds()),
            'entities' => count($this->getEntityIds()),
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
            'works/title' => $this->getTitleQueries(),
            'works/author' => $this->getAuthorWorkIds(),
            'works/name' => $this->getAuthorWorkQueries(),
            'series/title' => $this->getSeriesQueries(),
            'series/author' => $this->getAuthorSeriesIds(),
            'entities' => $this->getEntityIds(),
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
            'works/title' => $this->getTitleQuery($cacheEntry),
            'works/author' => $this->getAuthorWork($cacheEntry),
            'works/name' => $this->getAuthorWorkQuery($cacheEntry),
            'series/title' => $this->getSeriesQuery($cacheEntry),
            'series/author' => $this->getAuthorSeries($cacheEntry),
            'entities' => $this->getEntity($cacheEntry),
            default => throw new Exception('Invalid cache type'),
        };
        if ($this->hasCache($cacheFile)) {
            return $this->loadCache($cacheFile);
        }
        return null;
    }

    /**
     * Summary of parseSearchResult
     * @param array<mixed> $data
     * @param string $lang
     * @return SearchResult
     */
    public static function parseSearchResult($data, $lang = 'en')
    {
        return new SearchResult($data, $lang);
    }

    /**
     * Summary of parseEntity
     * @param array<mixed> $data
     * @param string $lang
     * @return Entity
     */
    public static function parseEntity($data, $lang = 'en')
    {
        $entity = new Entity($data, $lang);
        // @todo this generates warnings for missing prop, propertyLabel, qualifier etc.
        $entity->parseProperties($data['properties'] ?? []);
        return $entity;
    }
}
