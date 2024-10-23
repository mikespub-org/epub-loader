<?php
/**
 * GoodReadsCache class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\GoodReads;

use Marsender\EPubLoader\Metadata\BaseCache;
use Marsender\EPubLoader\Metadata\BookInfos;
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
        $authorMap = $result->getAuthorMap($authorId);
        if (empty($authorMap)) {
            return null;
        }
        $books = [];
        $bookList = $authorMap->getBooks() ?? [];
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
     * Summary of getBookInfos
     * @return array<string, ?BookInfos>
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
        $entry = $result->getProperties();
        // <a href="{{endpoint}}/{{action}}/{{dbNum}}/{{cacheName}}/{{cacheType}}?entry={{entry}}">{{entry}}</a>
        foreach ($entry as $authorId => $author) {
            $id = $author->getId();
            $cacheFile = $this->getAuthor($id);
            if ($this->hasCache($cacheFile)) {
                $entry[$authorId]->id = "<a href='{$urlPrefix}author/list?entry={$id}'>{$id}</a>";
            } else {
                $entry[$authorId]->id = "<a href='{$urlPrefix}author/list?entry={$id}'>{$id}</a> ?";
            }
            $author->books ??= [];
            foreach ($author->getBooks() as $key => $book) {
                $bookId = $book->getId();
                $entryId = GoodReadsMatch::bookid(bookId: $bookId);
                $cacheFile = $this->getBook($entryId);
                if ($this->hasCache($cacheFile)) {
                    $entry[$authorId]->books[$key]->id = "<a href='{$urlPrefix}book/show?entry={$entryId}'>{$bookId}</a>";
                } else {
                    $entry[$authorId]->books[$key]->id = "<a href='{$urlPrefix}book/show?entry={$entryId}'>{$bookId}</a> ?";
                }
            }
        }
        return $entry;
    }

    /**
     * Summary of formatSeries
     * @param array<mixed>|null $entry
     * @param string|null $urlPrefix
     * @param string $seriesId
     * @return array<mixed>|null
     */
    public function formatSeries($entry, $urlPrefix, $seriesId)
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
        /**
        foreach ($result->getBookList() as $key => $book) {
            if (empty($book->getBookId())) {
                continue;
            }
            $entryId = $book->getBookId();
            $cacheFile = $this->getBook($entryId);
            $url = $book->getBookUrl() ?? $entryId;
            if ($this->hasCache($cacheFile)) {
                $book->bookUrl = "<a href='{$urlPrefix}book/show?entry={$entryId}'>{$url}</a>";
            } else {
                $book->bookUrl = "<a href='{$urlPrefix}book/show?entry={$entryId}'>{$url}</a> ?";
            }
            if (!empty($book->getAuthor()) && !empty($book->getAuthor()->getId())) {
                $entryId = str_replace(GoodReadsMatch::AUTHOR_URL, '', $book->getAuthor()->getWorksListUrl() ?? '');
                $cacheFile = $this->getAuthor($entryId);
                $url = $book->getAuthor()->getWorksListUrl() ?? $entryId;
                if ($this->hasCache($cacheFile)) {
                    $book->author->worksListUrl = "<a href='{$urlPrefix}author/list?entry={$entryId}'>{$url}</a>";
                } else {
                    $book->author->worksListUrl = "<a href='{$urlPrefix}author/list?entry={$entryId}'>{$url}</a> ?";
                }
            }
            $result->bookList[$key] = $book;
        }
        return (array) $result;
         */
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
        $bookInfo = GoodReadsImport::load($this->cacheDir . '/goodreads', $result);
        $bookInfo = $this->formatBookInfo($bookInfo, $urlPrefix);
        return (array) $bookInfo;
        /**
        foreach ($result->getProps()->getPageProps()->getApolloState()->getBookMap() as $bookId => $book) {
            if (empty($book->getLegacyId())) {
                continue;
            }
            $entryId = $book->getLegacyId();
            $cacheFile = $this->getBook((string) $entryId);
            $url = $book->getWebUrl() ?? $entryId;
            if ($this->hasCache($cacheFile)) {
                $book->webUrl = "<a href='{$urlPrefix}book/show?entry={$entryId}'>{$url}</a>";
            } else {
                $book->webUrl = "<a href='{$urlPrefix}book/show?entry={$entryId}'>{$url}</a> ?";
            }
            $result->props->pageProps->apolloState->bookMap[$bookId] = $book;
        }
        foreach ($result->getProps()->getPageProps()->getApolloState()->getContributorMap() as $authorId => $author) {
            if (empty($author->getLegacyId())) {
                continue;
            }
            $entryId = str_replace(GoodReadsMatch::AUTHOR_URL, '', str_replace('/show/', '/list/', $author->getWebUrl() ?? ''));
            $cacheFile = $this->getAuthor($entryId);
            $url = $author->getWebUrl() ?? $author->getLegacyId();
            if ($this->hasCache($cacheFile)) {
                $author->webUrl = "<a href='{$urlPrefix}author/list?entry={$entryId}'>{$url}</a>";
            } else {
                $author->webUrl = "<a href='{$urlPrefix}author/list?entry={$entryId}'>{$url}</a> ?";
            }
            $result->props->pageProps->apolloState->contributorMap[$authorId] = $author;
        }
        foreach ($result->getProps()->getPageProps()->getApolloState()->getSeriesMap() as $seriesId => $series) {
            if (empty($series->getWebUrl()) || !str_starts_with($series->getWebUrl(), GoodReadsMatch::SERIES_URL)) {
                continue;
            }
            $entryId = str_replace(GoodReadsMatch::SERIES_URL, '', $series->getWebUrl());
            $cacheFile = $this->getSeries($entryId);
            $url = $series->getWebUrl();
            if ($this->hasCache($cacheFile)) {
                $series->webUrl = "<a href='{$urlPrefix}series?entry={$entryId}'>{$url}</a>";
            } else {
                $series->webUrl = "<a href='{$urlPrefix}series?entry={$entryId}'>{$url}</a> ?";
            }
            $result->props->pageProps->apolloState->seriesMap[$seriesId] = $series;
        }
        foreach ($result->getProps()->getPageProps()->getApolloState()->getReviewMap() as $reviewId => $review) {
            $review->text = htmlspecialchars($review->getText() ?? '');
            $result->props->pageProps->apolloState->reviewMap[$reviewId] = $review;
        }
        return (array) $result;
         */
    }

    /**
     * Summary of formatBookInfo
     * @param BookInfos $bookInfo
     * @param string|null $urlPrefix
     * @return BookInfos
     */
    public function formatBookInfo($bookInfo, $urlPrefix)
    {
        $entryId = $bookInfo->mName;
        $cacheFile = $this->getBook((string) $entryId);
        $url = $bookInfo->mUri ?? $entryId;
        if ($this->hasCache($cacheFile)) {
            $bookInfo->mUri = "<a href='{$urlPrefix}book/show?entry={$entryId}'>{$url}</a>";
        } else {
            $bookInfo->mUri = "<a href='{$urlPrefix}book/show?entry={$entryId}'>{$url}</a> ?";
        }
        if (!empty($bookInfo->mAuthorIds)) {
            foreach ($bookInfo->mAuthorIds as $id => $authorId) {
                $cacheFile = $this->getAuthor($authorId);
                if ($this->hasCache($cacheFile)) {
                    $bookInfo->mAuthorIds[$id] = "<a href='{$urlPrefix}author/list?entry={$authorId}'>{$authorId}</a>";
                } else {
                    $bookInfo->mAuthorIds[$id] = "<a href='{$urlPrefix}author/list?entry={$authorId}'>{$authorId}</a> ?";
                }
            }
        }
        if (!empty($bookInfo->mSerieIds)) {
            foreach ($bookInfo->mSerieIds as $id => $seriesId) {
                $cacheFile = $this->getSeries($seriesId);
                if ($this->hasCache($cacheFile)) {
                    $bookInfo->mSerieIds[$id] = "<a href='{$urlPrefix}series?entry={$seriesId}'>{$seriesId}</a>";
                } else {
                    $bookInfo->mSerieIds[$id] = "<a href='{$urlPrefix}series?entry={$seriesId}'>{$seriesId}</a> ?";
                }
            }
        }
        return $bookInfo;
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
