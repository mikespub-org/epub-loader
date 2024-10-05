<?php
/**
 * GoodReadsMatch class - @todo
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\Sources;

use Marsender\EPubLoader\Import\BaseImport;
use Marsender\EPubLoader\Import\GoodReadsBook;
use Marsender\EPubLoader\Metadata\BookInfos;
use Marsender\EPubLoader\Metadata\GoodReads\Search\SearchResult;
use Marsender\EPubLoader\Metadata\GoodReads\Series\SeriesResult;
use Exception;

class GoodReadsMatch extends BaseMatch
{
    public const ENTITY_URL = 'https://www.goodreads.com/book/show/';
    public const ENTITY_PATTERN = '/^\d+(|\.\w+)$/';
    public const CACHE_TYPES = ['goodreads/book/show', 'goodreads/author/list', 'goodreads/series', 'goodreads/search'];
    public const QUERY_URL = 'https://www.goodreads.com/search?q={query}&search_type=books';  // &utf8=%E2%9C%93&tab=books&per_page={limit}
    public const AUTHOR_URL = 'https://www.goodreads.com/author/list/';
    public const SERIES_URL = 'https://www.goodreads.com/series/';

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
            $cacheFile = $this->cacheDir . '/goodreads/search/' . urlencode($query) . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        $url = str_replace('{query}', rawurlencode($query), static::QUERY_URL);
        $result = file_get_contents($url);
        $matched = $this->parseSearchPage($query, $result);
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $matched);
        }
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
            if ($this->cacheDir) {
                $cacheFile = $this->cacheDir . '/goodreads/search/' . urlencode($query) . '.htm';
                file_put_contents($cacheFile, $content);
            }
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
     * @param string|null $lang Language (default: en)
     * @return string|null
     */
    public function findAuthorId($author, $lang = null)
    {
        if (!empty($author['link']) && str_starts_with($author['link'], self::AUTHOR_URL)) {
            return str_replace(self::AUTHOR_URL, '', $author['link']);
        }
        $entityId = null;
        $query = $author['name'];
        $matched = $this->findAuthors($query, $lang);
        // @todo Find author with highest books count!?
        if (count($matched) > 0) {
            //$entityId = array_keys($matched)[0];
            uasort($matched, function ($a, $b) {
                return count($b['books']) <=> count($a['books']);
            });
            $entityId = array_keys($matched)[0];
        }
        return $entityId;
    }

    /**
     * Summary of getSearchQueries (url encoded)
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getSearchQueries($lang = null)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/goodreads/search/';
        return BaseImport::getFiles($baseDir, '*.json', true);
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
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/goodreads/author/list/' . $authorId . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        // https://www.goodreads.com/author/list/123.Author_Name?per_page=100
        $url = static::AUTHOR_URL . $authorId . '?per_page=100';
        $result = file_get_contents($url);
        $parsed = $this->parseAuthorPage($authorId, $result);
        // @todo remove other authors here?
        $entity = $parsed;
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $entity);
        }
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
            if ($this->cacheDir) {
                $cacheFile = $this->cacheDir . '/goodreads/author/list/' . $authorId . '.htm';
                file_put_contents($cacheFile, $content);
            }
            throw $e;
        }
    }

    /**
     * Summary of getAuthorIds
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getAuthorIds($lang = null)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/goodreads/author/list/';
        return BaseImport::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of getSeries
     * @param string $seriesId
     * @param string|null $lang Language (default: en)
     * @return array<mixed>
     */
    public function getSeries($seriesId, $lang = null)
    {
        $lang ??= $this->lang;
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/goodreads/series/' . $seriesId . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        // https://www.goodreads.com/series/123.Series_Name
        $url = static::SERIES_URL . $seriesId;
        $result = file_get_contents($url);
        $parsed = $this->parseSeriesPage($seriesId, $result);
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $parsed);
        }
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
            if ($this->cacheDir) {
                $cacheFile = $this->cacheDir . '/goodreads/series/' . $seriesId . '.htm';
                file_put_contents($cacheFile, $content);
            }
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
     * Summary of getSeriesIds
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getSeriesIds($lang = null)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/goodreads/series/';
        return BaseImport::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of getBook
     * @param string $bookId
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getBook($bookId, $lang = null)
    {
        $bookId = static::bookid($bookId);
        $lang ??= $this->lang;
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/goodreads/book/show/' . $bookId . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        // https://www.goodreads.com/book/show/123.Book_Title
        $url = static::link($bookId);
        $result = file_get_contents($url);
        $result = $this->parseBookPage($bookId, $result);
        $entity = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $entity);
        }
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
            if ($this->cacheDir) {
                $cacheFile = $this->cacheDir . '/goodreads/book/show/' . $bookId . '.htm';
                file_put_contents($cacheFile, $content);
            }
            throw new Exception('Unable to find JSON data in html page: see ' . $bookId . '.htm');
        }
        return $matches[1];
    }

    /**
     * Summary of getBookIds
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getBookIds($lang = null)
    {
        $lang ??= $this->lang;
        $baseDir = $this->cacheDir . '/goodreads/book/show/';
        return BaseImport::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of entity
     * @param string $link
     * @return string
     */
    public static function entity($link)
    {
        if (str_starts_with($link, static::AUTHOR_URL)) {
            return str_replace(static::AUTHOR_URL, '', $link);
        }
        if (str_starts_with($link, static::SERIES_URL)) {
            return str_replace(static::SERIES_URL, '', $link);
        }
        return str_replace(static::ENTITY_URL, '', $link);
    }

    /**
     * Summary of isValidLink
     * @param string $link
     * @return bool
     */
    public static function isValidLink($link)
    {
        if (!empty($link) && (str_starts_with($link, (string) static::ENTITY_URL) || str_starts_with($link, (string) static::AUTHOR_URL) || str_starts_with($link, (string) static::SERIES_URL))) {
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
     * Summary of import
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfos|SeriesResult|SearchResult
     */
    public static function import($dbPath, $data)
    {
        if (!empty($data["page"]) && $data["page"] == "/book/show/[book_id]") {
            return GoodReadsBook::import($dbPath, $data);
        }
        if (array_keys($data)[0] == 0) {
            return GoodReadsBook::parseSeries($data);
        }
        return GoodReadsBook::parseSearch($data);
    }
}
