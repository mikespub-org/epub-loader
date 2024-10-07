<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use PHPUnit\Framework\Attributes\Depends;
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
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppIndex(): void
    {
        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<div><b>Select action</b></div>';
        $this->assertStringContainsString($expected, $output);
    }

    /**
     * Summary of testAppUnknown
     * @return void
     */
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

    /**
     * Summary of testAppSelectDatabase
     * @return void
     */
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

    /**
     * Summary of testAppCsvExport
     * @return void
     */
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
        $expected = '/tests/BaseWithSomeBooks/BaseWithSomeBooks_metadata.csv - 2 files OK - 0 files Error';
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

    /**
     * Summary of testAppCsvDownload
     * @return void
     */
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

    public function testAppJsonImport(): void
    {
        $_SERVER['PATH_INFO'] = '/json_import/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/json_import">Import JSON files from Lookup into new Calibre database</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '/tests/BaseWithSomeBooks/./metadata_db_prefs_backup.json - 0 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppDbLoad
     * @return void
     */
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
        $expected = '/calibre/library/metadata.db - 164 files OK - 2 files Error';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppListAuthors
     * @return void
     */
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

    /**
     * Summary of testAppCacheStats
     * @return void
     */
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

    /**
     * Summary of testAppCacheEntries
     * @return void
     */
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

    /**
     * Summary of testAppInternal
     * @return void
     */
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
}
