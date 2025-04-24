<?php

/**
 * GoodReadsCache class
 */

namespace Marsender\EPubLoader\Metadata\GoodReads;

use Marsender\EPubLoader\Metadata\BaseCache;
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Marsender\EPubLoader\Metadata\GoodReads\Books\BookResult;
use Marsender\EPubLoader\Metadata\GoodReads\Search\AuthorEntry;
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
        return parent::getFiles($baseDir, '*.json', true);
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
        return parent::getFiles($baseDir, '*.json', true);
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
            if (empty($data[$authorId])) {
                continue;
            }
            // [authorId => author]
            $authors[$authorId] = $data[$authorId]['name'];
        }
        return $authors;
    }

    /**
     * Summary of getAuthorBooks
     * @param string $authorId
     * @return array<mixed>|null
     */
    public function getAuthorBooks($authorId)
    {
        $cacheFile = $this->getAuthor($authorId);
        if (!$this->hasCache($cacheFile)) {
            return null;
        }
        $data = $this->loadCache($cacheFile);
        if (empty($data)) {
            return null;
        }
        $result = self::parseSearch($data);
        $authorEntry = $result->getAuthorEntry($authorId);
        if (empty($authorEntry)) {
            return null;
        }
        $books = [];
        $bookList = $authorEntry->getBooks() ?? [];
        foreach ($bookList as $book) {
            $bookId = $book->getId() ?? count($books);
            $books[$bookId] = (array) $book;
        }
        return $books;
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
        return parent::getFiles($baseDir, '*.json', true);
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
        return parent::getFiles($baseDir, '*.json', true);
    }

    /**
     * Summary of getBookInfo
     * @return array<string, ?BookInfo>
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
                $books[$bookId] = GoodReadsImport::load($basePath, $bookResult, $this);
            } catch (Exception) {
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
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>
     */
    public function getEntries($cacheType, $sort = null, $offset = null)
    {
        $offset ??= 0;
        $entries = match ($cacheType) {
            'author/list' => $this->getAuthorIds(),
            'book/show' => $this->getBookIds(),
            'series' => $this->getSeriesIds(),
            'search' => $this->getSearchQueries(),
            default => throw new Exception('Invalid cache type'),
        };
        // we will order & slice later for mtime or size - see BaseCache::getSortedEntries()
        if (empty($sort) || !in_array($sort, ['mtime', 'size'])) {
            $entries = array_slice($entries, $offset, static::$limit);
        }
        $result = [];
        foreach ($entries as $entry) {
            $cacheFile = match ($cacheType) {
                'author/list' => $this->getAuthor($entry),
                'book/show' => $this->getBook($entry),
                'series' => $this->getSeries($entry),
                // we need to urldecode() first here
                'search' => $this->getSearchQuery(urldecode($entry)),
                default => throw new Exception('Invalid cache type'),
            };
            $result[$entry] = [
                'id' => $entry,
                'file' => $cacheFile,
                'mtime' => filemtime($cacheFile),
                'size' => filesize($cacheFile),
            ];
        }
        // we order & slice here for mtime or size
        $result = static::getSortedEntries($result, $sort, $offset);
        return $result;
    }

    /**
     * Summary of getEntry
     * @param string $cacheType
     * @param string $cacheEntry
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function getEntry($cacheType, $cacheEntry, $urlPrefix = null)
    {
        $cacheFile = match ($cacheType) {
            'author/list' => $this->getAuthor($cacheEntry),
            'book/show' => $this->getBook($cacheEntry),
            'series' => $this->getSeries($cacheEntry),
            'search' => $this->getSearchQuery($cacheEntry),
            default => throw new Exception('Invalid cache type'),
        };
        if ($this->hasCache($cacheFile)) {
            $entry = $this->loadCache($cacheFile);
            return match ($cacheType) {
                'author/list' => $this->formatSearch($entry, $urlPrefix),
                'book/show' => $this->formatBook($entry, $urlPrefix),
                // id is not available in JSON data - this must be set by caller
                'series' => $this->formatSeries($entry, $urlPrefix, $cacheEntry),
                'search' => $this->formatSearch($entry, $urlPrefix),
                default => $entry,
            };
        }
        return null;
    }

    /**
     * Summary of formatSearch
     * @param array<mixed>|null $entry
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function formatSearch($entry, $urlPrefix)
    {
        if (is_null($entry) || is_null($urlPrefix)) {
            return $entry;
        }
        $result = self::parseSearch($entry);
        $entries = GoodReadsImport::loadSearch($this->cacheDir . '/goodreads', $result);
        // <a href="{{endpoint}}/{{action}}/{{dbNum}}/{{cacheName}}/{{cacheType}}?entry={{entry}}">{{entry}}</a>
        foreach ($entries as $authorId => $authorInfo) {
            $authorInfo = self::formatAuthorInfo($authorInfo, $urlPrefix);
            $entries[$authorId] = $authorInfo;
        }
        return $entries;
    }

    /**
     * Summary of formatSeries
     * @param array<mixed>|null $entry
     * @param string|null $urlPrefix
     * @param string $seriesId
     * @return array<mixed>|null
     */
    public function formatSeries($entry, $urlPrefix, $seriesId): array|null
    {
        if (is_null($entry) || is_null($urlPrefix)) {
            return $entry;
        }
        $result = self::parseSeries($entry);
        // id is not available in JSON data - this must be set by caller
        $result->setId($seriesId);
        $series = GoodReadsImport::loadSeries($this->cacheDir . '/goodreads', $result);
        foreach ($series['bookList'] as $id => $bookInfo) {
            $bookInfo = $this->formatBookInfo($bookInfo, $urlPrefix);
            $series['bookList'][$id] = (array) $bookInfo;
        }
        return $series;
    }

    /**
     * Summary of formatBook
     * @param array<mixed>|null $entry
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function formatBook($entry, $urlPrefix)
    {
        if (is_null($entry) || is_null($urlPrefix)) {
            return $entry;
        }
        $result = self::parseBook($entry);
        $bookInfo = GoodReadsImport::load($this->cacheDir . '/goodreads', $result, $this);
        $bookInfo = $this->formatBookInfo($bookInfo, $urlPrefix);
        return (array) $bookInfo;
    }

    /**
     * Summary of formatBookInfo
     * @param BookInfo $bookInfo
     * @param string|null $urlPrefix
     * @return BookInfo
     */
    public function formatBookInfo($bookInfo, $urlPrefix)
    {
        $bookId = $bookInfo->id;
        $entryId = GoodReadsMatch::bookid($bookId);
        $cacheFile = $this->getBook($entryId);
        $url = $bookInfo->uri ?? $bookId;
        if ($this->hasCache($cacheFile)) {
            $bookInfo->uri = "<a href='{$urlPrefix}book/show?entry={$bookId}'>{$url}</a>";
        } else {
            $bookInfo->uri = "<a href='{$urlPrefix}book/show?entry={$bookId}'>{$url}</a> ?";
        }
        if (!empty($bookInfo->authors)) {
            foreach ($bookInfo->authors as $id => $authorInfo) {
                $authorInfo = self::formatAuthorInfo($authorInfo, $urlPrefix);
                $bookInfo->authors[$id] = $authorInfo;
            }
        }
        if (!empty($bookInfo->series)) {
            foreach ($bookInfo->series as $id => $seriesInfo) {
                $seriesInfo = self::formatSeriesInfo($seriesInfo, $urlPrefix);
                $bookInfo->series[$id] = $seriesInfo;
            }
        }
        return $bookInfo;
    }

    /**
     * Summary of formatAuthorInfo
     * @param AuthorInfo $authorInfo
     * @param string|null $urlPrefix
     * @return AuthorInfo
     */
    public function formatAuthorInfo($authorInfo, $urlPrefix)
    {
        $authorId = $authorInfo->id;
        $cacheFile = $this->getAuthor($authorId);
        if ($this->hasCache($cacheFile)) {
            $authorInfo->id = "<a href='{$urlPrefix}author/list?entry={$authorId}'>{$authorId}</a>";
        } else {
            $authorInfo->id = "<a href='{$urlPrefix}author/list?entry={$authorId}'>{$authorId}</a> ?";
        }
        if (!empty($authorInfo->books)) {
            foreach ($authorInfo->books as $id => $bookInfo) {
                $bookInfo = self::formatBookInfo($bookInfo, $urlPrefix);
                $authorInfo->books[$id] = $bookInfo;
            }
        }
        if (!empty($authorInfo->series)) {
            foreach ($authorInfo->series as $id => $seriesInfo) {
                $seriesInfo = self::formatSeriesInfo($seriesInfo, $urlPrefix);
                $authorInfo->series[$id] = $seriesInfo;
            }
        }
        return $authorInfo;
    }

    /**
     * Summary of formatSeriesInfo
     * @param SeriesInfo $seriesInfo
     * @param string|null $urlPrefix
     * @return SeriesInfo
     */
    public function formatSeriesInfo($seriesInfo, $urlPrefix)
    {
        $seriesId = $seriesInfo->id;
        $cacheFile = $this->getSeries($seriesId);
        if ($this->hasCache($cacheFile)) {
            $seriesInfo->id = "<a href='{$urlPrefix}series?entry={$seriesId}'>{$seriesId}</a>";
        } else {
            $seriesInfo->id = "<a href='{$urlPrefix}series?entry={$seriesId}'>{$seriesId}</a> ?";
        }
        if (!empty($seriesInfo->authors)) {
            foreach ($seriesInfo->authors as $id => $authorInfo) {
                $authorInfo = self::formatAuthorInfo($authorInfo, $urlPrefix);
                $seriesInfo->authors[$id] = $authorInfo;
            }
        }
        if (!empty($seriesInfo->books)) {
            foreach ($seriesInfo->books as $id => $bookInfo) {
                $bookInfo = self::formatBookInfo($bookInfo, $urlPrefix);
                $seriesInfo->books[$id] = $bookInfo;
            }
        }
        return $seriesInfo;
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
     * Parse JSON data for GoodReads author in search result
     *
     * @param array<mixed> $data
     *
     * @return AuthorEntry
     */
    public static function parseSearchAuthor($data)
    {
        $result = AuthorEntry::fromJson($data);
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
