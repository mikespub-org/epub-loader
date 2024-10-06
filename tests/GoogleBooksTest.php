<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\Import\JsonImport;
use Marsender\EPubLoader\Metadata\GoogleBooks\GoogleBooksCache;
use Marsender\EPubLoader\Metadata\GoogleBooks\GoogleBooksMatch;
use PHPUnit\Framework\TestCase;

class GoogleBooksTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!file_exists(dirname(__DIR__) . '/app/config.php')) {
            copy(dirname(__DIR__) . '/app/config.php.example', dirname(__DIR__) . '/app/config.php');
        }
        $_SERVER['SCRIPT_NAME'] = '/phpunit';
    }

    /**
     * Summary of testAppSearchBooks
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppSearchBooks(): void
    {
        $_SERVER['PATH_INFO'] = '/gb_books/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gb_books/0/1?lang=en&bookId=11">Search</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppSearchBookSearch
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppSearchBookSearch(): void
    {
        $_SERVER['PATH_INFO'] = '/gb_books/0/1';
        $_GET['bookId'] = '11';
        $_GET['lang'] = 'en';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gb_volume/0?lang=en&matchId=2BrZDQAAQBAJ">A Study in Scarlet</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'This first Holmes novel became the basis of the pilot episode of the extremely popular BBC show';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['bookId']);
        unset($_GET['lang']);
    }

    /**
     * Summary of testAppSearchVolume
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppSearchVolume(): void
    {
        $_SERVER['PATH_INFO'] = '/gb_volume/0';
        $_GET['matchId'] = '2BrZDQAAQBAJ';
        $_GET['lang'] = 'en';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'selfLink: https://www.googleapis.com/books/v1/volumes/2BrZDQAAQBAJ';
        $this->assertStringContainsString($expected, $output);
        $expected = 'title: A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['matchId']);
        unset($_GET['lang']);
    }

    public function testJsonImportFile(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/google';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonFile = $dbPath . '/authors/Arthur Conan Doyle.en.40.json';
        [$message, $errors] = $import->loadFromJsonFile($dbPath, $jsonFile);

        $expected = '/cache/google/authors/Arthur Conan Doyle.en.40.json - 40 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportPath(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/google';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        //$jsonPath = 'authors';
        $jsonPath = 'titles';
        [$message, $errors] = $import->loadFromPath($dbPath, $jsonPath);

        $expected = '/cache/google/titles/Émile Zola.La curée.fr.json - 10 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testFindSeriesByName(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoogleBooksMatch($cacheDir);
        $author = [
            'name' => 'Trudi Canavan',
        ];
        $query = 'Black Magician Trilogy';
        $series = $match->findSeriesByName($query, $author);

        $expected = 'books#volumes';
        $this->assertEquals($expected, $series['kind']);
        $expected = 41;
        $this->assertEquals($expected, $series['totalItems']);
        $expected = 40;
        $this->assertCount($expected, $series['items']);
    }

    public function testCacheParseAuthors(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoogleBooksCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/google/authors/', '*.en.40.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/google/authors/', '', $cacheFile);
            $query = str_replace('.en.40.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $authors = $cache::parseSearch($matched);
        }

        $expected = count($cache->getAuthorQueries('en', 40));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseSeries(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoogleBooksCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/google/series/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/google/series/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $series = $cache::parseSearch($matched);
        }

        $expected = count($cache->getSeriesQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseTitles(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoogleBooksCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/google/titles/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/google/titles/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $titles = $cache::parseSearch($matched);
        }

        $expected = count($cache->getTitleQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseVolume(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoogleBooksCache(cacheDir: $cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/google/volumes/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $volumeId = str_replace($cacheDir . '/google/volumes/', '', $cacheFile);
            $volumeId = str_replace('.en.json', '', $volumeId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $volume = $cache::parseVolume($matched);
        }

        $expected = count($cache->getVolumeIds('en'));
        $this->assertCount($expected, $fileList);
    }
}
