<?php
/**
 * Epub loader application test
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

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
     * @runInSeparateProcess
     * @return void
     */
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
     * @runInSeparateProcess
     * @return void
     */
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
     * @runInSeparateProcess
     * @return void
     */
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
}
