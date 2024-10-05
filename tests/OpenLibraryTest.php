<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\Import\BaseImport;
use Marsender\EPubLoader\Import\JsonImport;
use Marsender\EPubLoader\Import\DataCapture;
use Marsender\EPubLoader\Metadata\Sources\OpenLibraryMatch;
use PHPUnit\Framework\TestCase;

class OpenLibraryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!file_exists(dirname(__DIR__) . '/app/config.php')) {
            copy(dirname(__DIR__) . '/app/config.php.example', dirname(__DIR__) . '/app/config.php');
        }
        $_SERVER['SCRIPT_NAME'] = '/phpunit';
    }

    /**
     * Summary of testAppFindAuthors
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppFindAuthors(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_author/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/ol_author/0">Some Books</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppFindAuthorLinks
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppFindAuthorLinks(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_author/0';
        $_GET['findLinks'] = '1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/ol_work/0/?matchId=OL13066A">OL13066A</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'H. G. Wells';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['findLinks']);
    }

    /**
     * Summary of testAppFindBooks
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppFindBooks(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_books/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/ol_books/0/1?bookId=11">Search</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppFindBookSearch
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppFindBookSearch(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_books/0/1';
        $_GET['bookId'] = '11';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/ol_work/0/1?bookId=11&matchId=OL262496W">OL262496W</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['bookId']);
    }

    /**
     * Summary of testAppFindAuthor
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppFindAuthor(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_author/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/ol_work/0/1?matchId=OL161167A">OL161167A</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppFindWorkAuthor
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppFindWorkAuthor(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_work/0/1';
        $_GET['matchId'] = 'OL161167A';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'wikipedia: http://en.wikipedia.org/wiki/Arthur_Conan_Doyle';
        $this->assertStringContainsString($expected, $output);
        $expected = 'name: Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['matchId']);
    }

    /**
     * Summary of testAppFindWork
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppFindWork(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_work/0/1';
        $_GET['bookId'] = '11';
        $_GET['matchId'] = 'OL262496W';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'key: /works/OL262496W';
        $this->assertStringContainsString($expected, $output);
        $expected = 'title: A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['bookId']);
        unset($_GET['matchId']);
    }

    public function testJsonImportFile(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/openlibrary';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonFile = $dbPath . '/entities/OL118974W.en.json';
        [$message, $errors] = $import->loadFromJsonFile($dbPath, $jsonFile);

        $expected = '/cache/openlibrary/entities/OL118974W.en.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportPath(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/openlibrary';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonPath = 'entities';
        [$message, $errors] = $import->loadFromPath($dbPath, $jsonPath);

        $expected = '/cache/openlibrary/entities/OL118974W.en.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testMatchParseAuthorSearch(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new OpenLibraryMatch($cacheDir);

        $fileList = BaseImport::getFiles($cacheDir . '/openlibrary/authors/', '*.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/openlibrary/authors/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$authors = $match->parseSearchPage($query, $content);
            $authors = OpenLibraryMatch::parseAuthorSearch($matched);
        }

        $expected = 1817;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseWorksByAuthor(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new OpenLibraryMatch($cacheDir);

        $fileList = BaseImport::getFiles($cacheDir . '/openlibrary/works/author/', '*.json');
        foreach ($fileList as $cacheFile) {
            $authorId = str_replace($cacheDir . '/openlibrary/works/author/', '', $cacheFile);
            $authorId = str_replace('.en.100.json', '', $authorId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$works = $match->parseAuthorPage($authorId, $content);
            $works = OpenLibraryMatch::parseWorkSearch($matched);
        }

        $expected = 1691;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseWorksByTitle(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new OpenLibraryMatch($cacheDir);

        $fileList = BaseImport::getFiles($cacheDir . '/openlibrary/works/title/', '*.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/openlibrary/works/title/', '', $cacheFile);
            $query = str_replace('.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$works = $match->parseSearchPage($query, $content);
            $works = OpenLibraryMatch::parseWorkSearch($matched);
        }

        $expected = 10;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseAuthorEntity(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new OpenLibraryMatch($cacheDir);
        //$patterns = ['.remote_ids.properties' => '^\w+$'];
        //$patterns = ['.remoteIds.properties' => '^\w+$'];
        //$capture = new DataCapture($patterns);

        $fileList = BaseImport::getFiles($cacheDir . '/openlibrary/entities/', '*A.en.json');
        foreach ($fileList as $cacheFile) {
            $authorId = str_replace($cacheDir . '/openlibrary/entities/', '', $cacheFile);
            $authorId = str_replace('.en.json', '', $authorId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$capture->analyze($matched);
            //$author = $match->parseSearchPage($authorId, $content);
            $author = OpenLibraryMatch::parseAuthorEntity($matched);
            //$capture->analyze($author);
        }
        //$cacheFile = $cacheDir . '/openlibrary/authorentity.report.json';
        //$report = $capture->report($cacheFile);

        $expected = 27;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseWorkEntity(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        //$match = new OpenLibraryMatch($cacheDir);
        //$patterns = [];
        //$capture = new DataCapture($patterns);

        $fileList = BaseImport::getFiles($cacheDir . '/openlibrary/entities/', '*W.en.json');
        foreach ($fileList as $cacheFile) {
            $workId = str_replace($cacheDir . '/openlibrary/entities/', '', $cacheFile);
            $workId = str_replace('.en.json', '', $workId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            //$capture->analyze($matched);
            //$work = $match->parseSearchPage($workId, $content);
            $work = OpenLibraryMatch::parseWorkEntity($matched);
            //$capture->analyze($work);
        }
        //$cacheFile = $cacheDir . '/openlibrary/workentity.report.json';
        //$report = $capture->report($cacheFile);

        $expected = 27;
        $this->assertCount($expected, $fileList);
    }
}
