<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\Import\JsonImport;
use Marsender\EPubLoader\Import\GoodReadsBook;
use Marsender\EPubLoader\Metadata\Sources\GoodReadsMatch;
use PHPUnit\Framework\TestCase;

class GoodReadsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!file_exists(dirname(__DIR__) . '/app/config.php')) {
            copy(dirname(__DIR__) . '/app/config.php.example', dirname(__DIR__) . '/app/config.php');
        }
        $_SERVER['SCRIPT_NAME'] = '/phpunit';
    }

    public function testJsonImportFile(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonFile = $dbPath . '/book/show/7112495.json';
        [$message, $errors] = $import->loadFromJsonFile($dbPath, $jsonFile);

        $expected = '/cache/goodreads/book/show/7112495.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportPath(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonPath = 'book/show';
        [$message, $errors] = $import->loadFromPath($dbPath, $jsonPath);

        $expected = '/cache/goodreads/book/show/7112495.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testMatchGetBook(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $bookId = '2306655';
        $book = $match->getBook($bookId);

        $expected = '/book/show/[book_id]';
        $this->assertEquals($expected, $book['page']);
    }

    public function testMatchGetBookParsed(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
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
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $seriesId = '102510-titan';
        $series = $match->getSeries($seriesId);

        $expected = 'Titan Series';
        $this->assertEquals($expected, $series[0][1]['title']);
    }

    public function testMatchGetSeriesParsed(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
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
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $query = 'David Mitchell';
        $result = $match->findAuthors($query);

        $expected = '6538289.David_Mitchell';
        $this->assertArrayHasKey($expected, $result);
    }

    public function testMatchFindAuthorsParsed(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
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
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $author = ['name' => 'David Mitchell'];
        $result = $match->findAuthorId($author);

        $expected = '6538289.David_Mitchell';
        $this->assertEquals($expected, $result);
    }

    public function testMatchGetAuthor(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $authorId = '6538289.David_Mitchell';
        $author = $match->getAuthor($authorId);

        $expected = '6538289.David_Mitchell';
        $this->assertArrayHasKey($expected, $author);
    }

    public function testMatchGetAuthorParsed(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
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
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $fileList = JsonImport::getFiles($cacheDir . '/goodreads/author/list/', '*.htm');
        foreach ($fileList as $cacheFile) {
            $jsonFile = str_replace('.htm', '.json', $cacheFile);
            if (file_exists($jsonFile)) {
                continue;
            }
            $authorId = str_replace($cacheDir . '/goodreads/author/list/', '', $cacheFile);
            $authorId = str_replace('.htm', '', $authorId);
            $content = file_get_contents($cacheFile);
            $author = $match->parseAuthorPage($authorId, $content);
            $match->saveCache($jsonFile, $author);
        }

        $expected = 1;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseSearch(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $fileList = JsonImport::getFiles($cacheDir . '/goodreads/search/', '*.htm');
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
            $match->saveCache($jsonFile, $matched);
        }

        $expected = 1;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseAuthorResult(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new GoodReadsMatch($cacheDir);

        $fileList = JsonImport::getFiles($cacheDir . '/goodreads/author/list/', '*.json');
        foreach ($fileList as $cacheFile) {
            $authorId = str_replace($cacheDir . '/goodreads/author/list/', '', $cacheFile);
            $authorId = str_replace('.json', '', $authorId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$authors = $match->parseAuthorPage($authorId, $content);
            $authors = GoodReadsBook::parseResult($matched);
        }

        $expected = 3;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseSearchResult(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new GoodReadsMatch($cacheDir);

        $fileList = JsonImport::getFiles($cacheDir . '/goodreads/search/', '*.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/goodreads/search/', '', $cacheFile);
            $query = str_replace('.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$authors = $match->parseSearchPage($query, $content);
            $authors = GoodReadsBook::parseResult($matched);
        }

        $expected = 3;
        $this->assertCount($expected, $fileList);
    }

    protected function skipTestMatchParseSeries(): void
    {
        // @todo ...
    }

    public function testMatchParseBook(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new GoodReadsMatch($cacheDir);

        $fileList = JsonImport::getFiles($cacheDir . '/goodreads/book/show/', '*.json');
        foreach ($fileList as $cacheFile) {
            $bookId = str_replace($cacheDir . '/goodreads/book/show/', '', $cacheFile);
            $bookId = str_replace('.json', '', $bookId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$book = $match->parseBookPage($bookId, $content);
            $book = GoodReadsBook::parse($matched);
        }

        $expected = 3;
        $this->assertCount($expected, $fileList);
    }
}
