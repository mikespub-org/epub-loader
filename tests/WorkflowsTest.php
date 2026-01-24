<?php

/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsCache;
use PHPUnit\Framework\Attributes\Depends;

class WorkflowsTest extends BaseTestCase
{
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCsvExport(): void
    {
        $_SERVER['PATH_INFO'] = '/csv_export/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/csv_export">Export CSV records with available epub files</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '/tests/BaseWithSomeBooks/. - 2 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[Depends('testAppCsvExport')]
    public function testAppCsvImport(): void
    {
        $_SERVER['PATH_INFO'] = '/csv_import/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/csv_import">Import CSV records into new Calibre database</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '/tests/BaseWithSomeBooks/BaseWithSomeBooks_metadata.csv - 2 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCsvDownload(): void
    {
        $_SERVER['PATH_INFO'] = '/csv_export/0';
        $_GET['download'] = '1';
        putenv('PHPUNIT_TESTING=1');

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = "'Alice''s Adventures in Wonderland - Lewis Carroll'";
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['download']);
        putenv('PHPUNIT_TESTING=');
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCsvDump(): void
    {
        $_SERVER['PATH_INFO'] = '/csv_dump/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/csv_dump">Dump CSV records from Calibre database</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '/tests/BaseWithSomeBooks/books - 16 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppJsonExport(): void
    {
        $_SERVER['PATH_INFO'] = '/json_export/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/json_export">Export JSON records with available epub files</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '/tests/BaseWithSomeBooks/. - 2 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[Depends('testAppJsonExport')]
    public function testAppJsonImport(): void
    {
        $_SERVER['PATH_INFO'] = '/json_import/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/json_import">Import JSON records into new Calibre database</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '/tests/BaseWithSomeBooks/./BaseWithSomeBooks_metadata.json - 2 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppJsonDownload(): void
    {
        $_SERVER['PATH_INFO'] = '/json_export/0';
        $_GET['download'] = '1';
        putenv('PHPUNIT_TESTING=1');

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '"Alice\'s Adventures in Wonderland - Lewis Carroll"';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['download']);
        putenv('PHPUNIT_TESTING=');
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppJsonDump(): void
    {
        $_SERVER['PATH_INFO'] = '/json_dump/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/json_dump">Dump JSON records from Calibre database</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '/tests/BaseWithSomeBooks/books - 16 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    public function testAppCacheLoad(): void
    {
        $_SERVER['PATH_INFO'] = '/cache_load/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/cache_load">Load JSON files from Lookup cache into new Calibre database</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '/tests/BaseWithSomeBooks/./metadata_db_prefs_backup.json - 0 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppDbLoad(): void
    {
        // @see https://github.com/w3c/epub-tests - files with 2 errors: duplicate uuid + epub version 0
        $_SERVER['PATH_INFO'] = '/db_load/4';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/db_load">Create Calibre database with available epub files</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '/calibre/library/. - 164 files OK - 1 files Error';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppCallback(): void
    {
        $_SERVER['PATH_INFO'] = '/callback/0/goodreads/book/show';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/callback">Export metadata cache info via callbacks</a>';
        $this->assertStringContainsString($expected, $output);

        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoodReadsCache($cacheDir);
        $bookIdList = $cache->getBookIds();

        $expected = 'Total write to goodreads/book/show - ' . count($bookIdList) . ' files OK - 0 files Error';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }
}
