<?php
/**
 * Epub loader application test
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

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
     * @runInSeparateProcess
     * @return void
     */
    public function testAppFindAuthors(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_author/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>Epub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/ol_author/0">Some Books</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppFindAuthorLinks
     * @runInSeparateProcess
     * @return void
     */
    public function testAppFindAuthorLinks(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_author/0';
        $_GET['findLinks'] = '1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>Epub Loader</title>';
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
     * @runInSeparateProcess
     * @return void
     */
    public function testAppFindBooks(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_books/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>Epub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/ol_books/0/1?bookId=11">Search</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppFindBookSearch
     * @runInSeparateProcess
     * @return void
     */
    public function testAppFindBookSearch(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_books/0/1';
        $_GET['bookId'] = '11';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>Epub Loader</title>';
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
     * @runInSeparateProcess
     * @return void
     */
    public function testAppFindAuthor(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_author/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>Epub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/ol_work/0/1?matchId=OL161167A">OL161167A</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppFindWorkAuthor
     * @runInSeparateProcess
     * @return void
     */
    public function testAppFindWorkAuthor(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_work/0/1';
        $_GET['matchId'] = 'OL161167A';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>Epub Loader</title>';
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
     * @runInSeparateProcess
     * @return void
     */
    public function testAppFindWork(): void
    {
        $_SERVER['PATH_INFO'] = '/ol_work/0/1';
        $_GET['bookId'] = '11';
        $_GET['matchId'] = 'OL262496W';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>Epub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'key: /works/OL262496W';
        $this->assertStringContainsString($expected, $output);
        $expected = 'title: A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['bookId']);
        unset($_GET['matchId']);
    }
}
