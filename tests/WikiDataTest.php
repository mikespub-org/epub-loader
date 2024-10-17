<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\Import\DataCapture;
use Marsender\EPubLoader\Metadata\WikiData\WikiDataCache;
use PHPUnit\Framework\TestCase;

class WikiDataTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!file_exists(dirname(__DIR__) . '/app/config.php')) {
            copy(dirname(__DIR__) . '/app/config.php.example', dirname(__DIR__) . '/app/config.php');
        }
        $_SERVER['SCRIPT_NAME'] = '/phpunit';
    }

    /**
     * Summary of testAppCheckAuthors
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCheckAuthors(): void
    {
        $_SERVER['PATH_INFO'] = '/wd_author/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/wd_author/0">Some Books</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppCheckAuthorLinks
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCheckAuthorLinks(): void
    {
        $_SERVER['PATH_INFO'] = '/wd_author/0';
        $_GET['findLinks'] = '1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/wd_entity/0/4?matchId=Q42511">Q42511</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'H. G. Wells';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['findLinks']);
    }

    /**
     * Summary of testAppCheckAuthor
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCheckAuthor(): void
    {
        $_SERVER['PATH_INFO'] = '/wd_author/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/wd_entity/0/1?matchId=Q35610">Q35610</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppCheckBooks
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCheckBooks(): void
    {
        $_SERVER['PATH_INFO'] = '/wd_books/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/wd_books/0/1?bookId=11">Search</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppCheckBookSearch
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCheckBookSearch(): void
    {
        $_SERVER['PATH_INFO'] = '/wd_books/0/1';
        $_GET['bookId'] = '11';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/wd_entity/0/1?seriesId=1&bookId=11&matchId=Q223131">Q223131</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'first Sherlock Holmes novel by Sir Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['bookId']);
    }

    /**
     * Summary of testAppCheckSeries
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCheckSeries(): void
    {
        $_SERVER['PATH_INFO'] = '/wd_series/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/wd_series/0/1?seriesId=1">Search</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Sherlock Holmes';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppCheckBookSearch
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCheckSeriesSearch(): void
    {
        $_SERVER['PATH_INFO'] = '/wd_series/0/1';
        $_GET['seriesId'] = '1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/wd_entity/0/1?seriesId=1&matchId=Q4653">Q4653</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'fictional character (consulting detective) created by Sir Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['seriesId']);
    }

    /**
     * Summary of testAppCheckEntity
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCheckEntity(): void
    {
        $_SERVER['PATH_INFO'] = '/wd_entity/0/1';
        $_GET['bookId'] = '11';
        $_GET['matchId'] = 'Q223131';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="https://en.wikipedia.org/wiki/A_Study_in_Scarlet">Wikipedia</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'description: first Sherlock Holmes novel by Sir Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['bookId']);
        unset($_GET['matchId']);
    }

    public function testCacheParseAuthorSearch(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/authors/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/wikidata/authors/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $authors = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getAuthorQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseWorksByAuthor(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/works/author/', '*.en.100.json');
        foreach ($fileList as $cacheFile) {
            $authorId = str_replace($cacheDir . '/wikidata/works/author/', '', $cacheFile);
            $authorId = str_replace('.en.100.json', '', $authorId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $works = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getAuthorWorkIds('en', 100));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseWorksByTitle(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/works/title/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/wikidata/works/title/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $works = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getTitleQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseWorksByName(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/works/name/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/wikidata/works/name/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $works = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getAuthorWorkQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseSeriesByAuthor(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/series/author/', '*.en.100.json');
        foreach ($fileList as $cacheFile) {
            $authorId = str_replace($cacheDir . '/wikidata/series/author/', '', $cacheFile);
            $authorId = str_replace('.en.100.json', '', $authorId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $works = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getAuthorSeriesIds('en', 100));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseSeriesByTitle(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/series/title/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/wikidata/series/title/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $works = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getSeriesQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseEntity(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);
        //$patterns = ['.properties' => '^P\d+$'];
        //$capture = new DataCapture($patterns);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/entities/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $entityId = str_replace($cacheDir . '/wikidata/entities/', '', $cacheFile);
            $entityId = str_replace('.json', '', $entityId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$capture->analyze($matched);
            $work = $cache::parseEntity($matched);
            //$capture->analyze($work);
        }
        //$cacheFile = $cacheDir . '/wikidata/entity.report.json';
        //$report = $capture->report($cacheFile);

        $expected = count($cache->getEntityIds('en'));
        $this->assertCount($expected, $fileList);
    }
}
