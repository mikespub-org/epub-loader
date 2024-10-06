<?php
/**
 * GoogleBooksMatch class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\Sources;

use Marsender\EPubLoader\Import\BaseImport;
use Marsender\EPubLoader\Import\GoogleBooksVolume;
use Marsender\EPubLoader\Metadata\BookInfos;

class GoogleBooksMatch extends BaseMatch
{
    public const ENTITY_URL = 'https://www.googleapis.com/books/v1/volumes/';
    public const ENTITY_PATTERN = '/^[\w-]+$/';
    public const CACHE_TYPES = ['google/authors', 'google/titles', 'google/series', 'google/volumes'];
    public const QUERY_URL = 'https://www.googleapis.com/books/v1/volumes?q={query}&maxResults={limit}&printType=books&projection={full}&langRestrict={lang}';

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
        usleep(static::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of getAuthorQueries (url encoded)
     * @param string|null $lang Language (default: en)
     * @param string|int|null $limit Max count of returning items (default: 40)
     * @return array<string, mixed>
     */
    public function getAuthorQueries($lang = null, $limit = 40)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/google/authors/';
        return BaseImport::getFiles($baseDir, '*.' . $lang . '.' . $limit . '.json', true);
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
        if ($this->cacheDir) {
            if (!empty($author)) {
                $cacheFile = $this->cacheDir . '/google/titles/' . $author['name'] . '.' . $query . '.' . $lang . '.json';
            } else {
                $cacheFile = $this->cacheDir . '/google/titles/' . $query . '.' . $lang . '.json';
            }
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
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
        $baseDir = $this->cacheDir . '/google/titles/';
        return BaseImport::getFiles($baseDir, '*.' . $lang . '.json', true);
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
        if ($this->cacheDir) {
            if (!empty($author)) {
                $cacheFile = $this->cacheDir . '/google/series/' . $author['name'] . '.' . $query . '.' . $lang . '.json';
            } else {
                $cacheFile = $this->cacheDir . '/google/series/' . $query . '.' . $lang . '.json';
            }
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
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
        $baseDir = $this->cacheDir . '/google/series/';
        return BaseImport::getFiles($baseDir, '*.' . $lang . '.json', true);
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
        usleep(static::SLEEP_TIME);
        return $entity;
    }

    /**
     * Summary of getVolumeIds
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getVolumeIds($lang = null)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/google/volumes/';
        return BaseImport::getFiles($baseDir, '*.' . $lang . '.json', true);
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

    /**
     * Summary of import
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfos|array<BookInfos>
     */
    public static function import($dbPath, $data)
    {
        return GoogleBooksVolume::import($dbPath, $data);
    }
}
