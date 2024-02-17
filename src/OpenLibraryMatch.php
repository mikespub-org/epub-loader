<?php
/**
 * OpenLibraryMatch class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

class OpenLibraryMatch extends BaseMatch
{
    public const ENTITY_URL = 'https://openlibrary.org/works/';
    public const ENTITY_PATTERN = '/^OL\d+/';
    public const CACHE_TYPES = ['openlibrary/authors', 'openlibrary/works', 'openlibrary/editions', 'openlibrary/ratings'];

    /**
     * Summary of findAuthors
     * @param string $query
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 10)
     * @return array<string, mixed>
     */
    public function findAuthors($query, $lang = null, $limit = 10)
    {
        // Find match on Wikidata
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/openlibrary/authors/' . $query . '.' . $lang . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        // https://openlibrary.org/dev/docs/api/authors
        $url = 'https://openlibrary.org/search/authors.json?q=' . rawurlencode($query);
        $results = file_get_contents($url);
        $matched = json_decode($results, true);
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $matched);
        }
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
        $matchId = null;
        $query = $author['name'];
        $matched = $this->findAuthors($query, $lang);
        // @todo Find works from author with highest work_count!?
        if (count($matched['docs']) > 0) {
            usort($matched['docs'], function ($a, $b) {
                return $b['work_count'] <=> $a['work_count'];
            });
            $matchId = $matched['docs'][0]['key'];
        }
        return $matchId;
    }

    /**
     * Summary of findWorksByAuthorId
     * @param string $matchId
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 10)
     * @return array<string, mixed>
     */
    public function findWorksByAuthorId($matchId, $lang = null, $limit = 100)
    {
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/openlibrary/authors/' . $matchId . '.' . $lang . '.' . $limit . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        // https://openlibrary.org/dev/docs/api/authors
        $url = 'https://openlibrary.org/authors/' . $matchId . '/works.json?limit=' . $limit;
        $results = file_get_contents($url);
        $matched = json_decode($results, true);
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $matched);
        }
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
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/openlibrary/works/' . $query . '.' . $lang . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        // https://openlibrary.org/dev/docs/api/search
        $url = 'https://openlibrary.org/search.json?title=' . rawurlencode($query) . '&fields=key,type,title,edition_count,first_publish_year,number_of_pages_median,author_name,author_key';
        $results = file_get_contents($url);
        $matched = json_decode($results, true);
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $matched);
        }
        return $matched;
    }

    /**
     * Summary of getWork
     * @param string $workId
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getWork($workId, $lang = null)
    {
        $lang ??= $this->lang;
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/openlibrary/works/' . $workId . '.' . $lang . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        // https://openlibrary.org/dev/docs/api/books
        $url = static::link($workId) . '.json';
        $result = file_get_contents($url);
        $entity = json_decode($result, true);
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $entity);
        }
        return $entity;
    }
}
