<?php

/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests\WikiData;

use Marsender\EPubLoader\Tests\BaseTestCase;

class WikiDataAppTest extends BaseTestCase
{
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
        require dirname(__DIR__, 2) . '/app/index.php';
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
}
