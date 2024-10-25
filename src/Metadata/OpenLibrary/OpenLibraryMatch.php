<?php
/**
 * OpenLibraryMatch class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\OpenLibrary;

use Marsender\EPubLoader\Metadata\BaseMatch;
use Marsender\EPubLoader\Models\AuthorInfo;

class OpenLibraryMatch extends BaseMatch
{
    public const ENTITY_URL = 'https://openlibrary.org/works/';
    public const ENTITY_PATTERN = '/^OL\d+/';
    public const AUTHOR_URL = 'https://openlibrary.org/authors/';

    /** @var OpenLibraryCache */
    protected $cache;

    /**
     * Summary of setCache
     * @param string|null $cacheDir
     * @return void
     */
    public function setCache($cacheDir)
    {
        $this->cache = new OpenLibraryCache($cacheDir);
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
            return ['numFound' => 0, 'start' => 0, 'numFoundExact' => true, 'docs' => []];
        }
        // Find match on Wikidata
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        $cacheFile = $this->cache->getAuthorQuery($query, $lang);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // https://openlibrary.org/dev/docs/api/authors
        $url = 'https://openlibrary.org/search/authors.json?q=' . rawurlencode($query);
        $results = file_get_contents($url, false, $this->context);
        $matched = json_decode($results, true);
        $this->cache->saveCache($cacheFile, $matched);
        usleep(parent::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of findAuthorId
     * @param array<mixed>|AuthorInfo $author
     * @param string|null $lang Language (default: en)
     * @return string|null
     */
    public function findAuthorId($author, $lang = null)
    {
        if (is_object($author)) {
            $author = (array) $author;
        }
        if (!empty($author['link']) && static::isValidLink($author['link'])) {
            return static::entity($author['link']);
        }
        $matchId = null;
        $query = $author['name'];
        $matched = $this->findAuthors($query, $lang);
        // @todo Find works from author with highest work_count!?
        if (!empty($matched) && count($matched['docs']) > 0) {
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
     * @param string|int|null $limit Max count of returning items (default: 100)
     * @return array<string, mixed>
     */
    public function findWorksByAuthorId($matchId, $lang = null, $limit = 100)
    {
        if (empty($matchId)) {
            return ['numFound' => 0, 'start' => 0, 'numFoundExact' => true, 'docs' => []];
        }
        $lang ??= $this->lang;
        $limit ??= $this->limit;
        $cacheFile = $this->cache->getAuthorWork($matchId, $lang, $limit);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // https://openlibrary.org/dev/docs/api/authors
        //$url = 'https://openlibrary.org/authors/' . $matchId . '/works.json?limit=' . $limit;
        // generic search returns 'docs' but author search returns 'entries'
        $url = 'https://openlibrary.org/search.json?author=' . $matchId . '&fields=key,type,title,edition_count,first_publish_year,number_of_pages_median,author_name,author_key';
        $results = file_get_contents($url, false, $this->context);
        $matched = json_decode($results, true);
        $this->cache->saveCache($cacheFile, $matched);
        usleep(parent::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of findWorksByTitle
     * @param string $query
     * @param string $authorName
     * @return array<string, mixed>
     */
    public function findWorksByTitle($query, $authorName)
    {
        if (empty($query)) {
            return ['numFound' => 0, 'start' => 0, 'numFoundExact' => true, 'docs' => []];
        }
        $cacheFile = $this->cache->getTitleQuery($query . '.' . $authorName);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // https://openlibrary.org/dev/docs/api/search
        $url = 'https://openlibrary.org/search.json?title=' . rawurlencode($query) . '&author=' . rawurlencode((string) $authorName) . '&fields=key,type,title,edition_count,first_publish_year,number_of_pages_median,author_name,author_key';
        $results = file_get_contents($url, false, $this->context);
        $matched = json_decode($results, true);
        if (empty($matched) || empty($matched['docs'])) {
            $url = 'https://openlibrary.org/search.json?title=' . rawurlencode($query) . '&fields=key,type,title,edition_count,first_publish_year,number_of_pages_median,author_name,author_key';
            $results = file_get_contents($url, false, $this->context);
            $matched = json_decode($results, true);
        }
        $this->cache->saveCache($cacheFile, $matched);
        usleep(parent::SLEEP_TIME);
        return $matched;
    }

    /**
     * Summary of getAuthor
     * @param string $authorId
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getAuthor($authorId, $lang = null)
    {
        $lang ??= $this->lang;
        $cacheFile = $this->cache->getAuthor($authorId, $lang);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // https://openlibrary.org/dev/docs/api/authors
        $url = self::AUTHOR_URL . $authorId . '.json';
        $result = file_get_contents($url, false, $this->context);
        $entity = json_decode($result, true);
        $this->cache->saveCache($cacheFile, $entity);
        usleep(parent::SLEEP_TIME);
        return $entity;
    }

    /**
     * Summary of getWork
     * @param string $workId
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getWork($workId, $lang = null)
    {
        if (str_ends_with($workId, 'A')) {
            return $this->getAuthor($workId);
        }
        $lang ??= $this->lang;
        $cacheFile = $this->cache->getWork($workId, $lang);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // https://openlibrary.org/dev/docs/api/books
        $url = static::link($workId) . '.json';
        $result = file_get_contents($url, false, $this->context);
        $entity = json_decode($result, true);
        $this->cache->saveCache($cacheFile, $entity);
        usleep(parent::SLEEP_TIME);
        return $entity;
    }

    /**
     * Summary of link
     * @param string $entityId
     * @return string
     */
    public static function link($entityId)
    {
        if (str_ends_with($entityId, 'A')) {
            return self::AUTHOR_URL . $entityId;
        }
        return self::ENTITY_URL . $entityId;
    }

    /**
     * Summary of entity
     * @param string $link
     * @return string
     */
    public static function entity($link)
    {
        if (str_ends_with($link, 'A')) {
            return str_replace(self::AUTHOR_URL, '', $link);
        }
        return str_replace(self::ENTITY_URL, '', $link);
    }

    /**
     * Summary of isValidLink
     * @param string $link
     * @return bool
     */
    public static function isValidLink($link)
    {
        if (!empty($link) && (str_starts_with($link, (string) self::ENTITY_URL) || str_starts_with($link, (string) self::AUTHOR_URL))) {
            return true;
        }
        return false;
    }
}
