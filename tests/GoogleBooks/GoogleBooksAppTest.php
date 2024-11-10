<?php
/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests\GoogleBooks;

use Marsender\EPubLoader\Tests\BaseTestCase;

class GoogleBooksAppTest extends BaseTestCase
{
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
}
