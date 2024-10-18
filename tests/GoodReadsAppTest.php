<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

class GoodReadsAppTest extends BaseTestCase
{
    /**
     * Summary of testAppGetAuthors
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetAuthors(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_author/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_author/0">Some Books</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppGetAuthorLinks
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetAuthorLinks(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_author/0';
        $_GET['findLinks'] = '1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_author/0/4?matchId=880695.H_G_Wells">880695.H_G_Wells</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'H. G. Wells';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['findLinks']);
    }

    /**
     * Summary of testAppGetAuthor
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetAuthor(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_author/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_books/0/1?authId=2448.Arthur_Conan_Doyle">2448.Arthur_Conan_Doyle</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppGetBooks
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetBooks(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_books/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_books/0/1?bookId=11">Search</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppGetBookSearch
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetBookSearch(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_books/0/1';
        $_GET['bookId'] = '11';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_books/0/1?seriesId=1&bookId=11&matchId=102868">102868</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'A Study in Scarlet (Sherlock Holmes, #1)';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['bookId']);
    }

    /**
     * Summary of testAppGetSeries
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetSeries(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_series/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_series/0/1?seriesId=1">Search</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Sherlock Holmes';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppGetSeriesMatch
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetSeriesMatch(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_series/0';
        $_GET['matchId'] = '49996-sherlock-holmes';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_series/0/?matchId=49996-sherlock-holmes">49996-sherlock-holmes</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Sherlock Holmes is a fictional consulting detective in London';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['seriesId']);
    }
}
