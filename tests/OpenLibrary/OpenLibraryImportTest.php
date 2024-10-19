<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests\OpenLibrary;

use Marsender\EPubLoader\Import\JsonImport;
use Marsender\EPubLoader\Tests\BaseTestCase;

class OpenLibraryImportTest extends BaseTestCase
{
    public function testJsonImportFile(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/openlibrary';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonFile = $dbPath . '/entities/OL118974W.en.json';
        [$message, $errors] = $import->loadFromJsonFile($dbPath, $jsonFile);

        $expected = '/cache/openlibrary/entities/OL118974W.en.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportPath(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/openlibrary';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonPath = 'entities';
        [$message, $errors] = $import->loadFromPath($dbPath, $jsonPath);

        $expected = '/cache/openlibrary/entities/OL118974W.en.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }
}
