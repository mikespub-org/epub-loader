<?php
/**
 * Epub loader application test
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use PHPUnit\Framework\TestCase;

class AppIndexTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!file_exists(dirname(__DIR__) . '/app/config.php')) {
            copy(dirname(__DIR__) . '/app/config.php.example', dirname(__DIR__) . '/app/config.php');
        }
        $_SERVER['SCRIPT_NAME'] = '/phpunit';
    }

    /**
     * Summary of testAppIndex
     * @runInSeparateProcess
     * @return void
     */
    public function testAppIndex(): void
    {
        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>Epub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<div><b>Select action</b></div>';
        $this->assertStringContainsString($expected, $output);
    }

    /**
     * Summary of testAppUnknown
     * @runInSeparateProcess
     * @return void
     */
    public function testAppUnknown(): void
    {
        $_SERVER['PATH_INFO'] = '/unknown/';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>Epub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Invalid action';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppSelectDatabase
     * @runInSeparateProcess
     * @return void
     */
    public function testAppSelectDatabase(): void
    {
        $_SERVER['PATH_INFO'] = '/authors/';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>Epub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<th>Db num</th>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<td>Some Books</td>';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppListAuthors
     * @runInSeparateProcess
     * @return void
     */
    public function testAppListAuthors(): void
    {
        $_SERVER['PATH_INFO'] = '/authors/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>Epub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/authors/0">Some Books</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }
}
