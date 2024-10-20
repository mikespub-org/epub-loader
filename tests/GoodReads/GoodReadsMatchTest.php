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

class GoodReadsMatchTest extends BaseTestCase
{
    public function testMatchGetBook(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $bookId = '2306655';
        $book = $match->getBook($bookId);

        $expected = '/book/show/[book_id]';
        $this->assertEquals($expected, $book['page']);
    }

    public function testMatchGetBookParsed(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $bookId = '2306655';
        $cacheFile = $cacheDir . '/goodreads/book/show/' . $bookId . '.htm';
        $content = file_get_contents($cacheFile);
        $result = $match->parseBookPage($bookId, $content);
        $book = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

        $expected = '/book/show/[book_id]';
        $this->assertEquals($expected, $book['page']);
    }

    public function testMatchGetSeries(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $seriesId = '102510-titan';
        $series = $match->getSeries($seriesId);

        $expected = 'Titan Series';
        $this->assertEquals($expected, $series[0][1]['title']);
    }

    public function testMatchGetSeriesParsed(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $seriesId = '102510-titan';
        $cacheFile = $cacheDir . '/goodreads/series/' . $seriesId . '.htm';
        $content = file_get_contents($cacheFile);
        $series = $match->parseSeriesPage($seriesId, $content);

        $expected = 'Titan Series';
        $this->assertEquals($expected, $series[0][1]['title']);
    }

    public function testMatchFindAuthors(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $query = 'David Mitchell';
        $result = $match->findAuthors($query);

        $expected = '6538289.David_Mitchell';
        $this->assertArrayHasKey($expected, $result);
    }

    public function testMatchFindAuthorsParsed(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $query = 'David Mitchell';
        $cacheFile = $cacheDir . '/goodreads/search/' . urlencode($query) . '.htm';
        $content = file_get_contents($cacheFile);
        $result = $match->parseSearchPage($query, $content);

        $expected = '6538289.David_Mitchell';
        $this->assertArrayHasKey($expected, $result);
    }

    public function testMatchFindAuthorId(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $author = ['name' => 'David Mitchell'];
        $result = $match->findAuthorId($author);

        $expected = '6538289.David_Mitchell';
        $this->assertEquals($expected, $result);
    }

    public function testMatchGetAuthor(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $authorId = '6538289.David_Mitchell';
        $author = $match->getAuthor($authorId);

        $expected = '6538289.David_Mitchell';
        $this->assertArrayHasKey($expected, $author);
    }

    public function testMatchGetAuthorParsed(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $authorId = '6538289.David_Mitchell';
        $cacheFile = $cacheDir . '/goodreads/author/list/' . $authorId . '.htm';
        $content = file_get_contents($cacheFile);
        $author = $match->parseAuthorPage($authorId, $content);

        $expected = '6538289.David_Mitchell';
        $this->assertArrayHasKey($expected, $author);
    }

    public function testMatchParseAuthorList(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $fileList = GoodReadsCache::getFiles($cacheDir . '/goodreads/author/list/', '*.htm');
        foreach ($fileList as $cacheFile) {
            $jsonFile = str_replace('.htm', '.json', $cacheFile);
            if (file_exists($jsonFile)) {
                continue;
            }
            $authorId = str_replace($cacheDir . '/goodreads/author/list/', '', $cacheFile);
            $authorId = str_replace('.htm', '', $authorId);
            $content = file_get_contents($cacheFile);
            $author = $match->parseAuthorPage($authorId, $content);
            $match->getCache()->saveCache($jsonFile, $author);
        }

        $expected = 1;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseSearch(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $fileList = GoodReadsCache::getFiles($cacheDir . '/goodreads/search/', '*.htm');
        foreach ($fileList as $cacheFile) {
            $jsonFile = str_replace('.htm', '.json', $cacheFile);
            if (file_exists($jsonFile)) {
                continue;
            }
            $query = str_replace($cacheDir . '/goodreads/search/', '', $cacheFile);
            $query = str_replace('.htm', '', $query);
            $query = urldecode($query);
            $content = file_get_contents($cacheFile);
            $matched = $match->parseSearchPage($query, $content);
            $match->getCache()->saveCache($jsonFile, $matched);
        }

        $expected = 1;
        $this->assertCount($expected, $fileList);
    }

    public function testFindAuthorByName(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $name = 'Arthur Conan Doyle';
        $authors = $match->findAuthorByName($name);

        $expected = '2448.Arthur_Conan_Doyle';
        $this->assertEquals($expected, array_key_first($authors));
    }

    public function testFindSeriesByTitle(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $title = 'Sherlock Holmes';
        $series = $match->findSeriesByTitle($title);

        $expected = 'Sherlock Holmes Series';
        $this->assertEquals($expected, $series[0][1]['title']);
    }
}
