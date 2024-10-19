<?php
/**
 * GoodReadsMatch class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\GoodReads;

use Marsender\EPubLoader\Metadata\BaseMatch;
use Exception;

class GoodReadsMatch extends BaseMatch
{
    public const ENTITY_URL = 'https://www.goodreads.com/book/show/';
    public const ENTITY_PATTERN = '/^\d+(|\.\w+)$/';
    public const QUERY_URL = 'https://www.goodreads.com/search?q={query}&search_type=books';  // &utf8=%E2%9C%93&tab=books&per_page={limit}
    public const AUTHOR_URL = 'https://www.goodreads.com/author/list/';
    public const SERIES_URL = 'https://www.goodreads.com/series/';
    public const SLEEP_TIME = 200000;

    /** @var GoodReadsCache */
    protected $cache;

    /**
     * Summary of setCache
     * @param string|null $cacheDir
     * @return void
     */
    public function setCache($cacheDir)
    {
        $this->cache = new GoodReadsCache($cacheDir);
    }

    /**
     * Summary of findAuthors
     * @param string $query
     * @return array<string, mixed>
     */
    public function findAuthors($query)
    {
        if (empty($query)) {
            return [];
        }
        // Find match on Wikidata
        // this will use urlencode($query)
        $cacheFile = $this->cache->getSearchQuery($query);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        $url = str_replace('{query}', rawurlencode($query), self::QUERY_URL);
        $result = file_get_contents($url, false, $this->context);
        $matched = $this->parseSearchPage($query, $result);
        $this->cache->saveCache($cacheFile, $matched);
        usleep(self::SLEEP_TIME);
        return $matched;
    }

    /**
     * Parse html page and get JSON array
     * @param string $query
     * @param string $content
     * @return array<mixed>
     */
    public function parseSearchPage($query, $content)
    {
        $content = preg_replace('~^.+?<table ~s', '', $content);
        $content = preg_replace('~<footer.+?$~s', '', $content);
        $result = [];
        $matches = [];
        if (!preg_match_all('~<tr itemscope itemtype="http://schema.org/Book">(.*?)</tr>~s', $content, $matches, PREG_SET_ORDER)) {
            if (str_contains($content, 'No results')) {
                return $result;
            }
            // save html page for testing
            // this will use urlencode($query)
            $cacheFile = str_replace('.json', '.htm', $this->cache->getSearchQuery($query));
            $this->cache->saveFile($cacheFile, $content);
            throw new Exception('Unable to find rows in html page: see ' . urlencode($query) . '.htm');
        }
        // support older format too
        if (str_contains($content, ' itemprop="url">')) {
            $titleMatch = '~<a class="bookTitle" href="([^"]+)" itemprop="url">\s*<span itemprop=\'name\'[^>]*>([^<]*)</span>\s*</a>~s';
            $authorMatch = '~<a class="authorName" href="([^"]+)" itemprop="url">\s*<span itemprop="name">([^<]*)</span>\s*</a>~s';
        } else {
            $titleMatch = '~<a class="bookTitle" itemprop="url" href="([^"]+)">\s*<span itemprop=\'name\'[^>]*>([^<]*)</span>\s*</a>~s';
            $authorMatch = '~<a class="authorName" itemprop="url" href="([^"]+)">\s*<span itemprop="name">([^<]*)</span>\s*</a>~s';
        }
        foreach ($matches as $match) {
            $row = $match[1];
            $title = [];
            // <span itemprop='name' role='heading' aria-level='4'>Cloud Atlas</span>
            if (!preg_match($titleMatch, $row, $title)) {
                echo $row;
                throw new Exception('Unable to find title in html row: see ' . urlencode($query) . '.htm');
            }
            $href = [];
            if (!preg_match('~/book/show/([^?]+)~', $title[1], $href)) {
                echo $title[1];
                throw new Exception('Unable to find bookId in html title: see ' . urlencode($query) . '.htm');
            }
            $bookId = $href[1];
            $bookTitle = trim(preg_replace('~\s+~', ' ', $title[2]));
            $book = [
                'id' => $bookId,
                'title' => $bookTitle,
            ];
            $image = [];
            if (preg_match('~<img alt="([^"]*)" class="bookCover" itemprop="image" src="([^"]*)" />~', $row, $image)) {
                $book['cover'] = trim($image[2]);
            }
            $rating = [];
            if (preg_match('~<span class="minirating">.*?([\d.]+) avg rating &mdash; ([\d,]+) ratings?</span>~', $row, $rating)) {
                $book['rating'] = (float) $rating[1];
                $book['count'] = (int) str_replace(',', '', $rating[2]);
            }
            // we could have multiple authors for one book here
            $authors = [];
            if (!preg_match_all($authorMatch, $row, $authors, PREG_SET_ORDER)) {
                echo $row;
                throw new Exception('Unable to find author in html row: see ' . urlencode($query) . '.htm');
            }
            foreach ($authors as $author) {
                $href = [];
                if (!preg_match('~/author/show/([^?]+)~', $author[1], $href)) {
                    echo $author[1];
                    throw new Exception('Unable to find authorId in html author: see ' . urlencode($query) . '.htm');
                }
                $authorId = $href[1];
                $authorName = trim(preg_replace('~\s+~', ' ', $author[2]));
                if (!isset($result[$authorId])) {
                    $result[$authorId] = [
                        'id' => $authorId,
                        'name' => $authorName,
                        'books' => [],
                    ];
                }
                $result[$authorId]['books'][] = $book;
            }
        }
        return $result;
    }

    /**
     * Summary of findAuthorId
     * @param array<mixed> $author
     * @return string|null
     */
    public function findAuthorId($author)
    {
        if (!empty($author['link']) && str_starts_with($author['link'], self::AUTHOR_URL)) {
            return str_replace(self::AUTHOR_URL, '', $author['link']);
        }
        $entityId = null;
        $query = $author['name'];
        $matched = $this->findAuthors($query);
        // @todo Find author with highest books count!?
        if (count($matched) > 0) {
            uasort($matched, function ($a, $b) {
                return count($b['books']) <=> count($a['books']);
            });
            $entityId = array_key_first($matched);
        }
        return $entityId;
    }

    /**
     * Summary of getAuthor
     * @param string $authorId
     * @return array<string, mixed>
     */
    public function getAuthor($authorId)
    {
        $cacheFile = $this->cache->getAuthor($authorId);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // https://www.goodreads.com/author/list/123.Author_Name?per_page=100
        $url = self::AUTHOR_URL . $authorId . '?per_page=100';
        $result = file_get_contents($url, false, $this->context);
        $parsed = $this->parseAuthorPage($authorId, $result);
        // @todo remove other authors here?
        $entity = $parsed;
        $this->cache->saveCache($cacheFile, $entity);
        usleep(self::SLEEP_TIME);
        return $entity;
    }

    /**
     * Parse html page and get JSON array
     * @param string $authorId
     * @param string $content
     * @return array<mixed>
     */
    public function parseAuthorPage($authorId, $content)
    {
        try {
            return $this->parseSearchPage($authorId, $content);
        } catch (Exception $e) {
            // save html page for testing
            $cacheFile = str_replace('.json', '.htm', $this->cache->getAuthor($authorId));
            $this->cache->saveFile($cacheFile, $content);
            throw $e;
        }
    }

    /**
     * Summary of getCachedAuthorNames
     * @internal using cached author lists
     * @return mixed
     */
    public function getCachedAuthorNames()
    {
        return $this->cache->cachedMethod('goodreads/author_names.json', [$this->cache, 'getAuthorNames']);
    }

    /**
     * Summary of findAuthorByName
     * @param string $name
     * @internal using cached author lists
     * @return array<mixed>
     */
    public function findAuthorByName($name)
    {
        $authors = $this->getCachedAuthorNames();
        // we could have several authors with the same name here
        $flipped = array_flip($authors);
        // exact match first
        if (array_key_exists($name, $flipped)) {
            $authorId = $flipped[$name];
            return $this->getAuthor($authorId);
        }
        // support partial match?
        foreach ($authors as $authorId => $authorName) {
            if (str_contains($authorName, $name)) {
                return $this->getAuthor($authorId);
            }
        }
        return [];
    }

    /**
     * Summary of getSeries
     * @param string $seriesId
     * @return array<mixed>
     */
    public function getSeries($seriesId)
    {
        $cacheFile = $this->cache->getSeries($seriesId);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // https://www.goodreads.com/series/123.Series_Name
        $url = self::SERIES_URL . $seriesId;
        $result = file_get_contents($url, false, $this->context);
        $parsed = $this->parseSeriesPage($seriesId, $result);
        $this->cache->saveCache($cacheFile, $parsed);
        usleep(self::SLEEP_TIME);
        return $parsed;
    }

    /**
     * Parse html page and get JSON array
     * @param string $seriesId
     * @param string $content
     * @return array<mixed>
     */
    public function parseSeriesPage($seriesId, $content)
    {
        $matches = [];
        if (!preg_match_all('~\sdata-react-class="([^"]*)"\s+data-react-props="([^"]*)"~', $content, $matches, PREG_SET_ORDER)) {
            // save html page for testing
            $cacheFile = str_replace('.json', '.htm', $this->cache->getSeries($seriesId));
            $this->cache->saveFile($cacheFile, $content);
            throw new Exception('Unable to find JSON data in html page: see ' . $seriesId . '.htm');
        }
        $result = [];
        foreach ($matches as $match) {
            if (in_array($match[1], ["ReactComponents.StoresInitializer", "ReactComponents.HeaderStoreConnector", "ReactComponents.ResponsivePageAdContainer"])) {
                continue;
            }
            $value = html_entity_decode($match[2]);
            $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            $result[] = [$match[1], $data];
        }
        return $result;
    }

    /**
     * Summary of getCachedSeriesTitles
     * @internal using cached series
     * @return mixed
     */
    public function getCachedSeriesTitles()
    {
        return $this->cache->cachedMethod('goodreads/series_titles.json', [$this->cache, 'getSeriesTitles']);
    }

    /**
     * Summary of findCachedSeriesId
     * @param string $title
     * @internal using cached series
     * @return string|null
     */
    public function findCachedSeriesId($title)
    {
        $series = $this->getCachedSeriesTitles();
        // we could have several series with the same title here
        $flipped = array_flip($series);
        // exact match first
        if (array_key_exists($title, $flipped)) {
            return $flipped[$title];
        }
        // support partial match?
        foreach ($series as $seriesId => $seriesTitle) {
            if (str_contains($seriesTitle, $title)) {
                return $seriesId;
            }
        }
        return null;
    }

    /**
     * Summary of findSeriesByTitle
     * @param string $title
     * @internal using cached series
     * @return array<mixed>
     */
    public function findSeriesByTitle($title)
    {
        $seriesId = $this->findCachedSeriesId($title);
        if (empty($seriesId)) {
            return [];
        }
        $found = $this->getSeries($seriesId);
        // id is not available in JSON data - this must be set by caller
        if (!empty($found) && isset($found[0][1]['title'])) {
            $found[0][1]['id'] = $seriesId;
        }
        return $found;
    }

    /**
     * Summary of getBook
     * @param string $bookId
     * @return array<string, mixed>
     */
    public function getBook($bookId)
    {
        // we only use the book # here, not the title
        $bookId = self::bookid($bookId);
        $cacheFile = $this->cache->getBook($bookId);
        if ($this->cache->hasCache($cacheFile)) {
            return $this->cache->loadCache($cacheFile);
        }
        // https://www.goodreads.com/book/show/123.Book_Title
        $url = static::link($bookId);
        $result = file_get_contents($url, false, $this->context);
        $result = $this->parseBookPage($bookId, $result);
        $entity = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        $this->cache->saveCache($cacheFile, $entity);
        usleep(self::SLEEP_TIME);
        return $entity;
    }

    /**
     * Parse html page and get JSON string
     * @param string $bookId
     * @param string $content
     * @return string
     */
    public function parseBookPage($bookId, $content)
    {
        $matches = [];
        if (!preg_match('~<script id="__NEXT_DATA__" type="application/json">(.+?)</script>~', $content, $matches)) {
            // save html page for testing
            $cacheFile = str_replace('.json', '.htm', $this->cache->getBook($bookId));
            $this->cache->saveFile($cacheFile, $content);
            throw new Exception('Unable to find JSON data in html page: see ' . $bookId . '.htm');
        }
        return $matches[1];
    }

    /**
     * Summary of entity
     * @param string $link
     * @return string
     */
    public static function entity($link)
    {
        if (str_starts_with($link, self::AUTHOR_URL)) {
            return str_replace(self::AUTHOR_URL, '', $link);
        }
        if (str_starts_with($link, self::SERIES_URL)) {
            return str_replace(self::SERIES_URL, '', $link);
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
        if (!empty($link) && (str_starts_with($link, (string) self::ENTITY_URL) || str_starts_with($link, (string) self::AUTHOR_URL) || str_starts_with($link, (string) self::SERIES_URL))) {
            return true;
        }
        return false;
    }

    /**
     * Summary of bookid
     * @param string $bookId
     * @return string
     */
    public static function bookid($bookId)
    {
        if (str_contains($bookId, '.')) {
            [$bookId, $title] = explode('.', $bookId);
        }
        if (str_contains($bookId, '-')) {
            [$bookId, $title] = explode('-', $bookId);
        }
        return $bookId;
    }

    /**
     * Summary of authorSlug - 9876.This_Author_Name
     * @param string $authorName
     * @return string
     */
    public static function authorSlug($authorName)
    {
        return preg_replace('/__+/', '_', str_replace([' ', '.', "'"], ['_', '_', '_'], $authorName));
    }

    /**
     * Summary of seriesSlug - 1234-this-series-title
     * @param string $seriesTitle
     * @return string
     */
    public static function seriesSlug($seriesTitle)
    {
        return preg_replace('/--+/', '-', str_replace([' ', '&', '*', "'", ':', '.', ',', '(', ')'], ['-', '-', '-', '-', '', '', '', '', ''], strtolower($seriesTitle)));
    }
}
