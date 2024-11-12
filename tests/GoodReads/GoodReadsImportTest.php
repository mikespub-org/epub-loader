<?php
/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests\GoodReads;

use Marsender\EPubLoader\Workflows\Import;
use Marsender\EPubLoader\Workflows\Workflow;
use Marsender\EPubLoader\Tests\BaseTestCase;

class GoodReadsImportTest extends BaseTestCase
{
    public function testJsonImportFile(): void
    {
        $dbPath = dirname(__DIR__, 2) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $sourceType = Workflow::JSON_FILES;
        $import = new Import($sourceType, $dbFile, true);

        $jsonFile = $dbPath . '/book/show/7112495.json';
        $result = $import->reader->getFromJsonFile($dbPath, $jsonFile);
        $message = implode("\n", $import->getMessages());
        $errors = $import->getErrors();

        $this->assertCount(1, $result);
        $expected = '/cache/goodreads/book/show/7112495.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportPath(): void
    {
        $dbPath = dirname(__DIR__, 2) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $sourceType = Workflow::JSON_FILES;
        $import = new Import($sourceType, $dbFile, true);

        $jsonPath = 'book/show';
        $import->process($dbPath, $jsonPath);
        $message = implode("\n", $import->getMessages());
        $errors = $import->getErrors();

        $expected = '/cache/goodreads/book/show/7112495.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }
}
