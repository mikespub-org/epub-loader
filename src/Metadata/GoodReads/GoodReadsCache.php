<?php
/**
 * GoodReadsCache class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\GoodReads;

use Marsender\EPubLoader\Metadata\BaseCache;
use Marsender\EPubLoader\Metadata\GoodReads\Books\BookResult;
use Marsender\EPubLoader\Metadata\GoodReads\Search\SearchResult;
use Marsender\EPubLoader\Metadata\GoodReads\Series\SeriesResult;
use Exception;

class GoodReadsCache extends BaseCache
{
    public const CACHE_TYPES = [
        'goodreads/author/list',
        'goodreads/book/show',
        'goodreads/series',
        'goodreads/search',
    ];

    /**
     * Summary of getSearchQuery (url encoded)
     * Path: '/goodreads/search/' . urlencode($query) . '.json'
     * @param string $query
     * @return string
     */
    public function getSearchQuery($query)
    {
        $cacheFile = $this->cacheDir . '/goodreads/search/' . urlencode($query) . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getSearchQueries (url encoded)
     * Path: '/goodreads/search/'
     * @return array<string>
     */
    public function getSearchQueries()
    {
        $baseDir = $this->cacheDir . '/goodreads/search/';
        return static::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of getAuthor
     * Path: '/goodreads/author/list/' . $authorId . '.json'
     * @param string $authorId
     * @return string
     */
    public function getAuthor($authorId)
    {
        $cacheFile = $this->cacheDir . '/goodreads/author/list/' . $authorId . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorIds
     * Path: '/goodreads/author/list/'
     * @return array<string>
     */
    public function getAuthorIds()
    {
        $baseDir = $this->cacheDir . '/goodreads/author/list/';
        return static::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of getAuthorNames
     * @return array<string, string>
     */
    public function getAuthorNames()
    {
        $authors = [];
        foreach ($this->getAuthorIds() as $authorId) {
            $cacheFile = $this->getAuthor($authorId);
            $data = $this->loadCache($cacheFile);
            // [authorId => author]
            $authors[$authorId] = $data[$authorId]['name'];
        }
        return $authors;
    }

    /**
     * Summary of getSeries
     * Path: '/goodreads/series/' . $seriesId . '.json'
     * @param string $seriesId
     * @return string
     */
    public function getSeries($seriesId)
    {
        $cacheFile = $this->cacheDir . '/goodreads/series/' . $seriesId . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getSeriesIds
     * Path: '/goodreads/series/'
     * @return array<string>
     */
    public function getSeriesIds()
    {
        $baseDir = $this->cacheDir . '/goodreads/series/';
        return static::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of getSeriesTitles
     * @return array<string, string>
     */
    public function getSeriesTitles()
    {
        $series = [];
        foreach ($this->getSeriesIds() as $seriesId) {
            $cacheFile = $this->getSeries($seriesId);
            $data = $this->loadCache($cacheFile);
            // [["ReactComponents.SeriesHeader", [...]], ...]
            $series[$seriesId] = $data[0][1]['title'];
        }
        return $series;
    }

    /**
     * Summary of getBook
     * Path: '/goodreads/book/show/' . $bookId . '.json'
     * @param string $bookId
     * @return string
     */
    public function getBook($bookId)
    {
        $bookId = static::bookid($bookId);
        $cacheFile = $this->cacheDir . '/goodreads/book/show/' . $bookId . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getBookIds
     * Path: '/goodreads/book/show/'
     * @return array<string>
     */
    public function getBookIds()
    {
        $baseDir = $this->cacheDir . '/goodreads/book/show/';
        return static::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of getBookInfos
     * @return array<string, mixed>
     */
    public function getBookInfos()
    {
        $basePath = $this->cacheDir . '/goodreads';
        $books = [];
        foreach ($this->getBookIds() as $bookId) {
            $cacheFile = $this->getBook($bookId);
            $data = $this->loadCache($cacheFile);
            if (empty($data)) {
                $books[$bookId] = null;
                continue;
            }
            try {
                $bookResult = self::parseBook($data);
                $books[$bookId] = GoodReadsImport::load($basePath, $bookResult);
            } catch (Exception $e) {
                $books[$bookId] = null;
            }
        }
        return $books;
    }

    /**
     * Summary of getStats
     * @return array<string, int>
     */
    public function getStats()
    {
        return [
            'author/list' => count($this->getAuthorIds()),
            'book/show' => count($this->getBookIds()),
            'series' => count($this->getSeriesIds()),
            'search' => count($this->getSearchQueries()),
        ];
    }

    /**
     * Summary of getEntries
     * @param string $cacheType
     * @param int|null $offset
     * @return array<mixed>
     */
    public function getEntries($cacheType, $offset = null)
    {
        $offset ??= 0;
        $entries = match ($cacheType) {
            'author/list' => $this->getAuthorIds(),
            'book/show' => $this->getBookIds(),
            'series' => $this->getSeriesIds(),
            'search' => $this->getSearchQueries(),
            default => throw new Exception('Invalid cache type'),
        };
        $entries = array_slice($entries, $offset, static::$limit);
        return $entries;
    }

    /**
     * Summary of getEntry
     * @param string $cacheType
     * @param string $cacheEntry
     * @return array<mixed>|null
     */
    public function getEntry($cacheType, $cacheEntry)
    {
        $cacheFile = match ($cacheType) {
            'author/list' => $this->getAuthor($cacheEntry),
            'book/show' => $this->getBook($cacheEntry),
            'series' => $this->getSeries($cacheEntry),
            'search' => $this->getSearchQuery($cacheEntry),
            default => throw new Exception('Invalid cache type'),
        };
        if ($this->hasCache($cacheFile)) {
            return $this->loadCache($cacheFile);
        }
        return null;
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
     * Parse JSON data for GoodReads search result
     *
     * @param array<mixed> $data
     *
     * @return SearchResult
     */
    public static function parseSearch($data)
    {
        $result = SearchResult::fromJson($data);
        return $result;
    }

    /**
     * Parse JSON data for GoodReads series result
     *
     * @param array<mixed> $data
     *
     * @return SeriesResult
     */
    public static function parseSeries($data)
    {
        $result = SeriesResult::fromJson($data);
        return $result;
    }

    /**
     * Parse JSON data for a GoodReads book
     *
     * @param array<mixed> $data
     *
     * @return BookResult
     */
    public static function parseBook($data)
    {
        $bookResult = BookResult::fromJson($data);
        return $bookResult;
    }
}
