<?php

/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests\OpenLibrary;

use Marsender\EPubLoader\Tests\BaseTestCase;

class OpenLibraryAppTest extends BaseTestCase
{
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/ol_work/0/4?matchId=OL13066A">OL13066A</a>';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
}
