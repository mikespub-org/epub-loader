<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests\GoodReads;

use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsCache;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsMatch;
use Marsender\EPubLoader\Tests\BaseTestCase;

class GoodReadsCacheTest extends BaseTestCase
{
    public static bool $download = false;

    public function testCacheParseAuthorResult(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $bookIdList = $cache->getBookIds();
        $missing = [
            'books' => [],
        ];
        $expected = $missing;

        $fileList = $cache::getFiles($cacheDir . '/goodreads/author/list/', '*.json');
        foreach ($fileList as $cacheFile) {
            $authorId = str_replace($cacheDir . '/goodreads/author/list/', '', $cacheFile);
            $authorId = str_replace('.json', '', $authorId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $result = $cache::parseSearch($matched);
            $authorEntry = $result->getAuthorEntry($authorId);
            if (empty($authorEntry)) {
                continue;
            }
            $count = 0;
            foreach ($authorEntry->getBooks() as $book) {
                if ($book->getCount() < 1000) {
                    break;
                }
                if ($count++ > 9) {
                    break;
                }
                $bookId = GoodReadsMatch::bookid($book->getId());
                if (!in_array($bookId, $bookIdList)) {
                    // @todo skip collections etc.
                    if (!preg_match('/#\d+-\d+/', $book->getTitle())) {
                        $missing['books'][$bookId] ??= $authorId . ' ' . $book->getTitle();
                    }
                }
            }
        }
        if (self::$download && count($missing['books']) > 0) {
            $match = new GoodReadsMatch($cacheDir);
            foreach ($missing['books'] as $bookId => $bookTitle) {
                $match->getBook($bookId);
            }
        }
        $this->assertEquals($expected, $missing);

        $expected = count($cache->getAuthorIds());
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseSearchResult(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/goodreads/search/', '*.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/goodreads/search/', '', $cacheFile);
            $query = str_replace('.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $authors = $cache::parseSearch($matched);
        }

        $expected = count($cache->getSearchQueries());
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseSeriesResult(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $bookIdList = $cache->getBookIds();
        $authorIdList = $cache->getAuthorIds();
        $missing = [
            'authors' => [],
            'books' => [],
        ];
        $expected = $missing;
        //$capture = new DataCapture();

        $fileList = $cache::getFiles($cacheDir . '/goodreads/series/', '*.json');
        foreach ($fileList as $cacheFile) {
            $seriesId = str_replace($cacheDir . '/goodreads/series/', '', $cacheFile);
            $seriesId = str_replace('.json', '', $seriesId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $series = $cache::parseSeries($matched);
            $series->setId($seriesId);
            // check for missing books and authors
            foreach ($series->getBookList() as $book) {
                $bookId = $book->getBookId();
                if (!in_array($bookId, $bookIdList)) {
                    // @todo skip collections, undefined series indexes and translations
                    $header = $book->getSeriesHeader();
                    if (!empty($header) && preg_match('/^Book\s+\d+$/', $header)) {
                        $missing['books'][$bookId] ??= $seriesId . ' ' . $header;
                    }
                }
                $author = $book->getAuthor();
                if (!empty($author)) {
                    $authorId = str_replace(GoodReadsMatch::AUTHOR_URL, '', $author->getWorksListUrl() ?? '');
                    if (!in_array($authorId, $authorIdList)) {
                        // @todo skip collection editors etc.
                        $header = $book->getSeriesHeader();
                        if (!empty($header) && preg_match('/^Book\s+\d+$/', $header)) {
                            $missing['authors'][$authorId] ??= $seriesId . ' ' . $header;
                        }
                    }
                }
            }
            //$capture->analyze($series);
        }
        //$cacheFile = $cacheDir . '/goodreads/series.report.json';
        //$report = $capture->report($cacheFile);
        if (self::$download && (count($missing['books']) > 0 || count($missing['authors']) > 0)) {
            $match = new GoodReadsMatch($cacheDir);
            foreach ($missing['books'] as $bookId => $seriesTitle) {
                $match->getBook($bookId);
            }
            foreach ($missing['authors'] as $authorId => $seriesTitle) {
                $match->getAuthor($authorId);
            }
        }
        $this->assertEquals($expected, $missing);

        $expected = count($cache->getSeriesIds());
        $this->assertCount($expected, $fileList);
    }

    protected function skipTestCacheParseBook(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/goodreads/book/show/', '*.json');
        foreach ($fileList as $cacheFile) {
            $bookId = str_replace($cacheDir . '/goodreads/book/show/', '', $cacheFile);
            $bookId = str_replace('.json', '', $bookId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $book = $cache::parseBook($matched);
        }

        $expected = count($cache->getBookIds());
        $this->assertCount($expected, $fileList);
    }

    public function testCacheGetBookInfos(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $books = $cache->getBookInfos();

        $expected = count($cache->getBookIds());
        $this->assertCount($expected, $books);

        $authorIdList = $cache->getAuthorIds();
        $seriesIdList = $cache->getSeriesIds();
        $missing = [
            'authors' => [],
            'books' => [],
            'series' => [],
        ];
        $expected = $missing;
        foreach ($books as $bookId => $book) {
            if (empty($book)) {
                $missing['books'][$bookId] ??= $bookId;
                continue;
            }
            if (!empty($book->authors) && count($book->authors) < 4) {
                foreach (array_keys($book->authors) as $authorId) {
                    if (!in_array($authorId, $authorIdList)) {
                        $missing['authors'][$authorId] ??= $book->title;
                    }
                }
            }
            if (!empty($book->series) && count($book->series) < 4) {
                foreach (array_keys($book->series) as $seriesId) {
                    if (!in_array($seriesId, $seriesIdList)) {
                        $missing['series'][$seriesId] ??= $book->title;
                    }
                }
            }
        }
        if (self::$download && (count($missing['books']) > 0 || count($missing['authors']) > 0 || count($missing['series']) > 0)) {
            $match = new GoodReadsMatch($cacheDir);
            foreach ($missing['books'] as $bookId => $bookTitle) {
                $match->getBook($bookId);
            }
            foreach ($missing['authors'] as $authorId => $bookTitle) {
                $match->getAuthor($authorId);
            }
            foreach ($missing['series'] as $seriesId => $bookTitle) {
                $match->getSeries($seriesId);
            }
        }
        $this->assertEquals($expected, $missing);
    }
}
