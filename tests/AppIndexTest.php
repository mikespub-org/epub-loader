<?php
/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests;

class AppIndexTest extends BaseTestCase
{
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppIndex(): void
    {
        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<b>Select action</b>';
        $this->assertStringContainsString($expected, $output);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppUnknown(): void
    {
        $_SERVER['PATH_INFO'] = '/unknown/';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Invalid action';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppSelectDatabase(): void
    {
        $_SERVER['PATH_INFO'] = '/authors/';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<th>Db num</th>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<td>Some Books</td>';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppListAuthors(): void
    {
        $_SERVER['PATH_INFO'] = '/authors/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/authors/0">Some Books</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppInternal(): void
    {
        $_SERVER['PATH_INFO'] = '/test/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/test">Test action (not visible)</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'ok';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppMeta(): void
    {
        $_SERVER['PATH_INFO'] = '/meta/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Metadata</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '>EPub Loader<';
        $this->assertStringContainsString($expected, $output);
        $expected = '>Lewis Carroll<';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }
}
