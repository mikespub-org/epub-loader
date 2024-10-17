<?php
/**
 * WikiDataMatch class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\WikiData;

use Marsender\EPubLoader\Metadata\BaseMatch;
use Wikidata\Wikidata;

class WikiDataMatch extends BaseMatch
{
    public const ENTITY_URL = 'http://www.wikidata.org/entity/';
    public const ENTITY_PATTERN = '/^Q\d+$/';
    public const AUTHOR_PROPERTY = 'P50';

    /** @var Wikidata|null */
    protected $api = null;
    /** @var WikiDataCache */
    protected $cache;

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
     * Summary of setCache
     * @param string|null $cacheDir
     * @return void
     */
    public function setCache($cacheDir)
    {
        $this->cache = new WikiDataCache($cacheDir);
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
        $cacheFile = $this->cache->getAuthorQuery($query, $lang);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        $results = $this->getApi()->search($query, $lang, $limit);
        $matched = [];
        foreach ($results as $id => $result) {
            $matched[$id] = (array) $result;
        }
        $this->cache->saveCache($cacheFile, $matched);
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
            $entityId = array_key_first($matched);
        }
        return $entityId;
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
        $cacheFile = $this->cache->getAuthorWork($entityId, $lang, $limit);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
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
        $this->cache->saveCache($cacheFile, $matched);
        usleep(static::SLEEP_TIME);
        return $matched;
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
        $cacheFile = $this->cache->getAuthorWorkQuery($query, $lang);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // Use P2093 when author property is unknown or does not exist
        $propId = 'P2093';
        $results = $this->getApi()->searchBy($propId, $query, $lang, $limit);
        $matched = [];
        foreach ($results as $id => $result) {
            $matched[$id] = (array) $result;
        }
        $this->cache->saveCache($cacheFile, $matched);
        usleep(static::SLEEP_TIME);
        return $matched;
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
        $cacheFile = $this->cache->getTitleQuery($query, $lang);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        $results = $this->getApi()->search($query, $lang, $limit);
        $matched = [];
        foreach ($results as $id => $result) {
            $matched[$id] = (array) $result;
        }
        $this->cache->saveCache($cacheFile, $matched);
        usleep(static::SLEEP_TIME);
        return $matched;
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
            $entityId = array_key_first($matched);
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
        $cacheFile = $this->cache->getAuthorSeries($entityId, $lang, $limit);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
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
        $this->cache->saveCache($cacheFile, $matched);
        usleep(static::SLEEP_TIME);
        return $matched;
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
        $cacheFile = $this->cache->getSeriesQuery($query, $lang);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        $results = $this->getApi()->search($query, $lang, $limit);
        $matched = [];
        foreach ($results as $id => $result) {
            $matched[$id] = (array) $result;
        }
        $this->cache->saveCache($cacheFile, $matched);
        usleep(static::SLEEP_TIME);
        return $matched;
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
        $cacheFile = $this->cache->getEntity($entityId, $lang);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        $result = $this->getApi()->get($entityId, $lang);
        $entity = $result->toArray();
        $entity = json_decode(json_encode($entity), true);
        $this->cache->saveCache($cacheFile, $entity);
        usleep(static::SLEEP_TIME);
        return $entity;
    }
}
