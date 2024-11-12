<?php
/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests;

class CacheHandlerTest extends BaseTestCase
{
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCacheStats(): void
    {
        $_SERVER['PATH_INFO'] = '/caches/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/caches">Cache statistics</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/caches/0?refresh=1" title="Refresh">Stats Updated</a>';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCacheEntries(): void
    {
        $_SERVER['PATH_INFO'] = '/caches/0/goodreads/author/list';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/caches">Cache statistics</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<b>Cache Entries</b>';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCacheEntry(): void
    {
        $_SERVER['PATH_INFO'] = '/caches/0/goodreads/author/list';
        $_GET['entry'] = '2448.Arthur_Conan_Doyle';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/caches">Cache statistics</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<b>Cache Entry 2448.Arthur_Conan_Doyle</b>';
        $this->assertStringContainsString($expected, $output);

        unset($_GET['entry']);
        unset($_SERVER['PATH_INFO']);
    }
}
