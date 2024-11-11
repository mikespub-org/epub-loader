<?php
/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests\GoogleBooks;

use Marsender\EPubLoader\Workflows\Import;
use Marsender\EPubLoader\Workflows\Workflow;
use Marsender\EPubLoader\Tests\BaseTestCase;

class GoogleBooksImportTest extends BaseTestCase
{
    public function testJsonImportVolume(): void
    {
        $dbPath = dirname(__DIR__, 2) . '/cache/google';
        $dbFile = $dbPath . '/metadata.db';
        $sourceType = Workflow::JSON_FILES;
        $import = new Import($sourceType, $dbFile, true);

        $jsonFile = $dbPath . '/volumes/_ogXogEACAAJ.en.json';
        $import->reader->loadFromJsonFile($dbPath, $jsonFile);
        $message = implode("\n", $import->getMessages());
        $errors = $import->getErrors();

        $expected = '/cache/google/volumes/_ogXogEACAAJ.en.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportFile(): void
    {
        $dbPath = dirname(__DIR__, 2) . '/cache/google';
        $dbFile = $dbPath . '/metadata.db';
        $sourceType = Workflow::JSON_FILES;
        $import = new Import($sourceType, $dbFile, true);

        $jsonFile = $dbPath . '/authors/Arthur Conan Doyle.en.40.json';
        $import->reader->loadFromJsonFile($dbPath, $jsonFile);
        $message = implode("\n", $import->getMessages());
        $errors = $import->getErrors();

        $expected = '/cache/google/authors/Arthur Conan Doyle.en.40.json - 40 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportPath(): void
    {
        $dbPath = dirname(__DIR__, 2) . '/cache/google';
        $dbFile = $dbPath . '/metadata.db';
        $sourceType = Workflow::JSON_FILES;
        $import = new Import($sourceType, $dbFile, true);

        //$jsonPath = 'authors';
        $jsonPath = 'titles';
        $import->process($dbPath, $jsonPath);
        $message = implode("\n", $import->getMessages());
        $errors = $import->getErrors();

        $expected = '/cache/google/titles/Émile Zola.La curée.fr.json - 10 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }
}
