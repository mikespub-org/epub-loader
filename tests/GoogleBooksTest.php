<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\Import\JsonImport;
use Marsender\EPubLoader\Import\GoogleBooksVolume;
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

        $expected = '<title>Epub Loader</title>';
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

        $expected = '<title>Epub Loader</title>';
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

        $expected = '<title>Epub Loader</title>';
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

    public function testMatchParseAuthors(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new GoogleBooksMatch($cacheDir);

        $fileList = JsonImport::getFiles($cacheDir . '/google/authors/', '*.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/google/authors/', '', $cacheFile);
            $query = str_replace('.en.40.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$authors = $match->parseSearchPage($query, $content);
            if (is_null($matched)) {
                continue;
            }
            $authors = GoogleBooksVolume::parseResult($matched);
        }

        $expected = 1832;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseSeries(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new GoogleBooksMatch($cacheDir);

        $fileList = JsonImport::getFiles($cacheDir . '/google/series/', '*.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/google/series/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$series = $match->parseSearchPage($query, $content);
            $series = GoogleBooksVolume::parseResult($matched);
        }

        $expected = 2;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseTitles(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new GoogleBooksMatch($cacheDir);

        $fileList = JsonImport::getFiles($cacheDir . '/google/titles/', '*.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/google/titles/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$titles = $match->parseSearchPage($query, $content);
            $titles = GoogleBooksVolume::parseResult($matched);
        }

        $expected = 38;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseVolume(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new GoogleBooksMatch($cacheDir);

        $fileList = JsonImport::getFiles($cacheDir . '/google/volumes/', '*.json');
        foreach ($fileList as $cacheFile) {
            $volumeId = str_replace($cacheDir . '/google/volumes/', '', $cacheFile);
            $volumeId = str_replace('.en.json', '', $volumeId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$volume = $match->parseSearchPage($volumeId, $content);
            $volume = GoogleBooksVolume::parse($matched);
        }

        $expected = 16;
        $this->assertCount($expected, $fileList);
    }
}
