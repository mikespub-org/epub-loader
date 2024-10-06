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
     * @return array<string, mixed>
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
     * @return array<string, mixed>
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
     * @return array<string, mixed>
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
     * @return array<string, mixed>
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
     * @return array<string, mixed>
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
     * @return array<string, mixed>
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
     * @return array<string, mixed>
     */
    public function getEntityIds($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/wikidata/entities/';
        return static::getFiles($baseDir, '*.' . $lang . '.json', true);
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
