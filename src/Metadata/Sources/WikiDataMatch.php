<?php
/**
 * WikiDataMatch class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\Sources;

use Marsender\EPubLoader\Import\BaseImport;
use Wikidata\Entity;
use Wikidata\SearchResult;
use Wikidata\Wikidata;

class WikiDataMatch extends BaseMatch
{
    public const ENTITY_URL = 'http://www.wikidata.org/entity/';
    public const ENTITY_PATTERN = '/^Q\d+$/';
    public const CACHE_TYPES = ['wikidata/authors', 'wikidata/works/author', 'wikidata/works/name', 'wikidata/works/title', 'wikidata/series/author', 'wikidata/series/title', 'wikidata/entities'];
    public const AUTHOR_PROPERTY = 'P50';

    /** @var Wikidata|null */
    protected $api = null;

    /**
     * Summary of getApi
     * @return Wikidata|null
     */
    protected function getApi()
    {
        $this->api ??= new Wikidata();
        return $this->api;
    }

    /**
     * Summary of findAuthors
     * @param string $query
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 10)
     * @return array<string, mixed>
     */
    public function findAuthors($query, $lang = null, $limit = 10)
    {
        if (empty($query)) {
            return [];
        }
        // Find match on Wikidata
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/wikidata/authors/' . $query . '.' . $lang . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        $results = $this->getApi()->search($query, $lang, $limit);
        $matched = [];
        foreach ($results as $id => $result) {
            $matched[$id] = (array) $result;
        }
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $matched);
        }
        usleep(static::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of findAuthorId
     * @param array<mixed> $author
     * @param string|null $lang Language (default: en)
     * @return string|null
     */
    public function findAuthorId($author, $lang = null)
    {
        if (!empty($author['link']) && static::isValidLink($author['link'])) {
            return static::entity($author['link']);
        }
        $entityId = null;
        $query = $author['name'];
        $matched = $this->findAuthors($query, $lang);
        // Find works from author for 1st match
        if (count($matched) > 0) {
            $entityId = array_keys($matched)[0];
        }
        return $entityId;
    }

    /**
     * Summary of getAuthorQueries (url encoded)
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getAuthorQueries($lang = null)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/wikidata/authors/';
        return BaseImport::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of findWorksByAuthorProperty
     * @param array<mixed> $author
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 10)
     * @return array<string, mixed>
     */
    public function findWorksByAuthorProperty($author, $lang = null, $limit = 100)
    {
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        $entityId = $this->findAuthorId($author, $lang);
        if (empty($entityId)) {
            return [];
        }
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/wikidata/works/author/' . $entityId . '.' . $lang . '.' . $limit . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        // Find literary works from author
        $propId = static::AUTHOR_PROPERTY;
        /**
        $propId = 'P31/wdt:P279* wd:Q7725634.
        ?item wdt:' . static::AUTHOR_PROPERTY;
    $query = '
            SELECT ?item WHERE {
                ?item wdt:' . $property . ' ' . $subject . '.
            } LIMIT ' . $limit . '
        ';
         */
        $results = $this->getApi()->searchBy($propId, $entityId, $lang, $limit);
        $matched = [];
        foreach ($results as $id => $result) {
            $matched[$id] = (array) $result;
        }
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $matched);
        }
        usleep(static::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of getAuthorWorkIds
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 100)
     * @return array<string, mixed>
     */
    public function getAuthorWorkIds($lang = null, $limit = 100)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/wikidata/works/author/';
        return BaseImport::getFiles($baseDir, '*.' . $lang . '.' . $limit . '.json', true);
    }

    /**
     * Summary of findWorksByAuthorName
     * @param array<mixed> $author
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 10)
     * @return array<string, mixed>
     */
    public function findWorksByAuthorName($author, $lang = null, $limit = 100)
    {
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        $query = $author['name'];
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/wikidata/works/name/' . $query . '.' . $lang . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        // Use P2093 when author property is unknown or does not exist
        $propId = 'P2093';
        $results = $this->getApi()->searchBy($propId, $query, $lang, $limit);
        $matched = [];
        foreach ($results as $id => $result) {
            $matched[$id] = (array) $result;
        }
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $matched);
        }
        usleep(static::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of getAuthorWorkQueries
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getAuthorWorkQueries($lang = null)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/wikidata/works/name/';
        return BaseImport::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of findWorksByTitle
     * @param string $query
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 10)
     * @return array<string, mixed>
     */
    public function findWorksByTitle($query, $lang = null, $limit = 10)
    {
        if (empty($query)) {
            return [];
        }
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/wikidata/works/title/' . $query . '.' . $lang . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        $results = $this->getApi()->search($query, $lang, $limit);
        $matched = [];
        foreach ($results as $id => $result) {
            $matched[$id] = (array) $result;
        }
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $matched);
        }
        usleep(static::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of getTitleQueries (url encoded)
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getTitleQueries($lang = null)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/wikidata/works/title/';
        return BaseImport::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of findWorkId - @todo
     * @param array<mixed> $author
     * @param array<mixed> $book
     * @param string|null $lang Language (default: en)
     * @return string|null
     */
    public function findWorkId($author, $book, $lang = null)
    {
        $lang ??= $this->lang;
        $authorId = $this->findAuthorId($author, $lang);
        $entityId = null;
        $query = $book['title'];
        $matched = $this->findAuthors($query, $lang);
        // Find works from author for 1st match
        if (count($matched) > 0) {
            $entityId = array_keys($matched)[0];
        }
        return $entityId;
    }

    /**
     * Summary of findSeriesByAuthor
     * @param array<mixed> $author
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 10)
     * @return array<string, mixed>
     */
    public function findSeriesByAuthor($author, $lang = null, $limit = 100)
    {
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        $entityId = $this->findAuthorId($author, $lang);
        if (empty($entityId)) {
            return [];
        }
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/wikidata/series/author/' . $entityId . '.' . $lang . '.' . $limit . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        // Find series of creative works from author
        //$propId = static::AUTHOR_PROPERTY;
        $propId = 'P31/wdt:P279* wd:Q7725310.
        ?item wdt:' . static::AUTHOR_PROPERTY;
        /**
    $query = '
            SELECT ?item WHERE {
                ?item wdt:' . $property . ' ' . $subject . '.
            } LIMIT ' . $limit . '
        ';
         */
        $results = $this->getApi()->searchBy($propId, $entityId, $lang, $limit);
        $matched = [];
        foreach ($results as $id => $result) {
            $matched[$id] = (array) $result;
        }
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $matched);
        }
        usleep(static::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of getAuthorSeriesIds
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 100)
     * @return array<string, mixed>
     */
    public function getAuthorSeriesIds($lang = null, $limit = 100)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/wikidata/series/author/';
        return BaseImport::getFiles($baseDir, '*.' . $lang . '.' . $limit . '.json', true);
    }

    /**
     * Summary of findSeriesByName
     * @param string $query
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 10)
     * @return array<string, mixed>
     */
    public function findSeriesByName($query, $lang = null, $limit = 10)
    {
        if (empty($query)) {
            return [];
        }
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/wikidata/series/title/' . $query . '.' . $lang . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        $results = $this->getApi()->search($query, $lang, $limit);
        $matched = [];
        foreach ($results as $id => $result) {
            $matched[$id] = (array) $result;
        }
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $matched);
        }
        usleep(static::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of getSeriesQueries (url encoded)
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getSeriesQueries($lang = null)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/wikidata/series/title/';
        return BaseImport::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getEntity
     * @param string $entityId
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getEntity($entityId, $lang = null)
    {
        $lang ??= $this->lang;
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/wikidata/entities/' . $entityId . '.' . $lang . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        $result = $this->getApi()->get($entityId, $lang);
        $entity = $result->toArray();
        $entity = json_decode(json_encode($entity), true);
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $entity);
        }
        usleep(static::SLEEP_TIME);
        return $entity;
    }

    /**
     * Summary of getEntityIds
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getEntityIds($lang = null)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/wikidata/entities/';
        return BaseImport::getFiles($baseDir, '*.' . $lang . '.json', true);
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
