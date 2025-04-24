<?php
/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests\WikiData;

use Marsender\EPubLoader\Workflows\Import;
use Marsender\EPubLoader\Workflows\Workflow;
use Marsender\EPubLoader\Workflows\Readers\JsonFileReader;
use Marsender\EPubLoader\Tests\BaseTestCase;

class WikiDataImportTest extends BaseTestCase
{
    public function testJsonImportFile(): void
    {
        $dbPath = dirname(__DIR__, 2) . '/cache/wikidata';
        $dbFile = $dbPath . '/metadata.db';
        $sourceType = Workflow::JSON_FILES;
        $import = new Import($sourceType, $dbFile, true);
        $expected = JsonFileReader::class;
        $this->assertInstanceOf($expected, $import->reader);

        $jsonFile = $dbPath . '/entities/Q223131.en.json';
        $result = $import->reader->getFromJsonFile($dbPath, $jsonFile);
        $message = implode("\n", $import->getMessages());
        $errors = $import->getErrors();

        $this->assertCount(1, $result);
        $expected = '/cache/wikidata/entities/Q223131.en.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportPath(): void
    {
        $dbPath = dirname(__DIR__, 2) . '/cache/wikidata';
        $dbFile = $dbPath . '/metadata.db';
        $sourceType = Workflow::JSON_FILES;
        $import = new Import($sourceType, $dbFile, true);

        $jsonPath = 'entities';
        $import->process($dbPath, $jsonPath);
        $message = implode("\n", $import->getMessages());
        $errors = $import->getErrors();

        $expected = '/cache/wikidata/entities/Q223131.en.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }
}
