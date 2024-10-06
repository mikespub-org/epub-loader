<?php
/**
 * GoogleBooksMatch class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

use Marsender\EPubLoader\Metadata\BaseMatch;

class GoogleBooksMatch extends BaseMatch
{
    public const ENTITY_URL = 'https://www.googleapis.com/books/v1/volumes/';
    public const ENTITY_PATTERN = '/^[\w-]+$/';
    public const QUERY_URL = 'https://www.googleapis.com/books/v1/volumes?q={query}&maxResults={limit}&printType=books&projection={full}&langRestrict={lang}';

    /** @var GoogleBooksCache */
    protected $cache;

    /**
     * Summary of setCache
     * @param string|null $cacheDir
     * @return void
     */
    public function setCache($cacheDir)
    {
        $this->cache = new GoogleBooksCache($cacheDir);
    }

    /**
     * Summary of getResults
     * @param string $query
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 10)
     * @param string $full Projection (default: full)
     * @return string
     */
    protected function getResults($query, $lang, $limit, $full = 'full')
    {
        $replace = [
            '{query}' => rawurlencode($query),
            '{lang}' => $lang,
            '{limit}' => $limit,
            '{full}' => $full,
        ];
        $url = str_replace(array_keys($replace), array_values($replace), static::QUERY_URL);
        $results = file_get_contents($url);
        return $results;
    }

    /**
     * Summary of findWorksByAuthor
     * @param array<mixed> $author
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 40)
     * @return array<string, mixed>
     */
    public function findWorksByAuthor($author, $lang = null, $limit = 40)
    {
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        $query = $author['name'];
        $cacheFile = $this->cache->getAuthorQuery($query, $lang, $limit);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // Find literary works from author
        $query = 'inauthor:"' . $query . '"';
        $results = $this->getResults($query, $lang, $limit);
        $matched = json_decode($results, true);
        $this->cache->saveCache($cacheFile, $matched);
        usleep(static::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of findWorksByTitle
     * @param string $query
     * @param array<mixed>|null $author
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 10)
     * @return array<string, mixed>
     */
    public function findWorksByTitle($query, $author = null, $lang = null, $limit = 10)
    {
        if (empty($query)) {
            return ['totalItems' => 0, 'items' => []];
        }
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        if (!empty($author)) {
            $cacheFile = $this->cache->getTitleQuery($author['name'] . '.' . $query, $lang);
        } else {
            $cacheFile = $this->cache->getTitleQuery($query, $lang);
        }
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // search by title and author first
        $query = 'intitle:"' . $query . '"';
        if (!empty($author)) {
            $query .= ' inauthor:"' . $author['name'] . '"';
        }
        $results = $this->getResults($query, $lang, $limit);
        $matched = json_decode($results, true);
        // fall back to search by title alone
        if (!empty($author) && (empty($matched) || $matched['totalItems'] == 0)) {
            $query = 'intitle:"' . $query . '"';
            $results = $this->getResults($query, $lang, $limit);
            $matched = json_decode($results, true);
        }
        $this->cache->saveCache($cacheFile, $matched);
        usleep(static::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of findSeriesByName
     * @param string $query
     * @param array<mixed> $author
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 40)
     * @return array<string, mixed>
     */
    public function findSeriesByName($query, $author, $lang = null, $limit = 40)
    {
        if (empty($query)) {
            return ['totalItems' => 0, 'items' => []];
        }
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        if (!empty($author)) {
            $cacheFile = $this->cache->getSeriesQuery($author['name'] . '.' . $query, $lang);
        } else {
            $cacheFile = $this->cache->getSeriesQuery($query, $lang);
        }
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // search by bibliogroup and author first
        $query = 'bibliogroup:"' . $query . '"';
        if (!empty($author)) {
            $query .= ' inauthor:"' . $author['name'] . '"';
        }
        $results = $this->getResults($query, $lang, $limit, 'lite');
        $matched = json_decode($results, true);
        // fall back to search by bibliogroup alone
        if (!empty($author) && (empty($matched) || $matched['totalItems'] == 0)) {
            $query = 'bibliogroup:"' . $query . '"';
            $results = $this->getResults($query, $lang, $limit, 'lite');
            $matched = json_decode($results, true);
        }
        $this->cache->saveCache($cacheFile, $matched);
        usleep(static::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of getVolume
     * @param string $volumeId
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getVolume($volumeId, $lang = null)
    {
        $lang ??= $this->lang;
        $cacheFile = $this->cache->getVolume($volumeId, $lang);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        $url = static::link($volumeId);
        $result = file_get_contents($url);
        $entity = json_decode($result, true);
        $this->cache->saveCache($cacheFile, $entity);
        usleep(static::SLEEP_TIME);
        return $entity;
    }

    /**
     * Summary of getLanguages
     * @return array<string, string>
     */
    public static function getLanguages()
    {
        return [
            'en' => 'English',
            'fr' => 'Français',
        ];
    }
}
