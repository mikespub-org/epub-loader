<?php
/**
 * GoogleMatch class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

class GoogleMatch extends BaseMatch
{
    public const ENTITY_URL = 'https://www.googleapis.com/books/v1/volumes/';
    public const CACHE_TYPES = ['google/authors', 'google/titles', 'google/volumes'];
    public const QUERY_URL = 'https://www.googleapis.com/books/v1/volumes?q={query}&maxResults={limit}&printType=books&projection=full&langRestrict={lang}';

    /**
     * Summary of getResults
     * @param string $query
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 10)
     * @return string
     */
    protected function getResults($query, $lang, $limit)
    {
        $replace = [
            '{query}' => rawurlencode($query),
            '{lang}' => $lang,
            '{limit}' => $limit,
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
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/google/authors/' . $query . '.' . $lang . '.' . $limit . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        // Find literary works from author
        $query = 'inauthor:"' . $query . '"';
        $results = $this->getResults($query, $lang, $limit);
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
            $cacheFile = $this->cacheDir . '/google/titles/' . $query . '.' . $lang . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        $query = 'intitle:"' . $query . '"';
        $results = $this->getResults($query, $lang, $limit);
        $matched = json_decode($results, true);
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $matched);
        }
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
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/google/volumes/' . $volumeId . '.' . $lang . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        $url = static::link($volumeId);
        $result = file_get_contents($url);
        $entity = json_decode($result, true);
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $entity);
        }
        return $entity;
    }
}
