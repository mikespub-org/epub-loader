<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\Import\JsonImport;

class GoodReadsImportTest extends BaseTestCase
{
    public function testJsonImportFile(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonFile = $dbPath . '/book/show/7112495.json';
        [$message, $errors] = $import->loadFromJsonFile($dbPath, $jsonFile);

        $expected = '/cache/goodreads/book/show/7112495.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportPath(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonPath = 'book/show';
        [$message, $errors] = $import->loadFromPath($dbPath, $jsonPath);

        $expected = '/cache/goodreads/book/show/7112495.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }
}
