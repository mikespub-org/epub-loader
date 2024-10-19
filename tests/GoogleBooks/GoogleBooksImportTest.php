<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests\GoogleBooks;

use Marsender\EPubLoader\Import\JsonImport;
use Marsender\EPubLoader\Tests\BaseTestCase;

class GoogleBooksImportTest extends BaseTestCase
{
    public function testJsonImportVolume(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/google';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonFile = $dbPath . '/volumes/_ogXogEACAAJ.en.json';
        [$message, $errors] = $import->loadFromJsonFile($dbPath, $jsonFile);

        $expected = '/cache/google/volumes/_ogXogEACAAJ.en.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportFile(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/google';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonFile = $dbPath . '/authors/Arthur Conan Doyle.en.40.json';
        [$message, $errors] = $import->loadFromJsonFile($dbPath, $jsonFile);

        $expected = '/cache/google/authors/Arthur Conan Doyle.en.40.json - 40 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportPath(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/google';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        //$jsonPath = 'authors';
        $jsonPath = 'titles';
        [$message, $errors] = $import->loadFromPath($dbPath, $jsonPath);

        $expected = '/cache/google/titles/Émile Zola.La curée.fr.json - 10 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }
}
