<?php
/**
 * ExportHandler class
 */

namespace Marsender\EPubLoader\Handlers;

use Marsender\EPubLoader\ActionHandler;
use Marsender\EPubLoader\Workflows\Converters\CalibreIdMapper;
use Marsender\EPubLoader\Workflows\Export;
use Marsender\EPubLoader\Workflows\Workflow;
use Marsender\EPubLoader\RequestHandler;
use Exception;

class ExportHandler extends ActionHandler
{
    /**
     * Summary of handle
     * @param string $action
     * @param RequestHandler $request
     * @return mixed
     */
    public function handle($action, $request)
    {
        $this->request = $request;
        switch ($action) {
            case 'csv_export':
                $result = $this->csv_export();
                break;
            case 'csv_dump':
                $result = $this->csv_dump();
                break;
            case 'json_export':
                $result = $this->json_export();
                break;
            case 'json_dump':
                $result = $this->json_dump();
                break;
            case 'callback':
                // use last part of /action/dbNum/authorId here
                $cacheName = $this->request->get('authorId');
                $result = $this->callback($cacheName);
                break;
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of csv_export
     * @return string|null
     */
    public function csv_export()
    {
        return $this->doExport(Workflow::LOCAL_BOOKS, Workflow::CSV_FILES);
    }

    /**
     * Summary of csv_dump
     * @return string|null
     */
    public function csv_dump()
    {
        return $this->doExport(Workflow::CALIBRE_DB, Workflow::CSV_FILES);
    }

    /**
     * Summary of json_export
     * @return string|null
     */
    public function json_export()
    {
        return $this->doExport(Workflow::LOCAL_BOOKS, Workflow::JSON_FILES);
    }

    /**
     * Summary of json_dump
     * @return string|null
     */
    public function json_dump()
    {
        return $this->doExport(Workflow::CALIBRE_DB, Workflow::JSON_FILES);
    }

    /**
     * Summary of callback
     * @param ?string $cacheName
     * @return array<mixed>|string|null
     */
    public function callback($cacheName = null)
    {
        $result = [];
        if (empty($this->cacheDir)) {
            return $result;
        }
        $result['cacheUpdated'] = 'never';
        $result = CacheHandler::getCacheStats($this->cacheDir);
        $result['callbacks'] = $this->request->getCallbacks();
        if (empty($result['callbacks'])) {
            $result['callbacks'] = [
                'setBookInfo' => [TestHandler::class, 'testCallback'],
                'setAuthorInfo' => [TestHandler::class, 'testCallback'],
                'setSeriesInfo' => [TestHandler::class, 'testCallback'],
            ];
        }
        if (empty($cacheName)) {
            return $result;
        }
        $result['cacheName'] = $cacheName;
        $result['cacheType'] = $this->request->getPath();
        if (empty($result['cacheType'])) {
            return $result;
        }
        $sourceType = Workflow::CACHE_TYPE;
        $sourcePath = $this->cacheDir;
        $targetType = Workflow::CALLBACK;
        $targetPath = $result['callbacks'];
        $workflow = Workflow::getWorkflow($sourceType, $sourcePath, $targetType, $targetPath);
        // @todo get bookId etc. from somewhere
        $dbPath = $this->dbConfig['db_path'];
        $dbFileName = $dbPath . DIRECTORY_SEPARATOR . 'metadata.db';
        $typeName = match ($cacheName) {
            'goodreads' => 'goodreads',
            'googlebooks' => 'google',
            'openlibrary' => 'olid',
            'wikidata' => 'wd',
            default => throw new Exception('Invalid cache name'),
        };
        $converter = new CalibreIdMapper($dbFileName, $typeName);
        $workflow->converters[] = $converter;
        $workflow->process($result['cacheName'], $result['cacheType']);
        $result['messages'] = $workflow->getMessages();
        $result['errors'] = $workflow->getErrors();
        $result['counters'] = $converter->getStats();
        return $result;
    }

    /**
     * Summary of do_export
     * @param int $sourceType Source type
     * @param int $targetType Target type
     * @return string|null
     */
    protected function doExport($sourceType, $targetType)
    {
        // Init csv file
        $dbPath = $this->dbConfig['db_path'];
        if ($sourceType == Workflow::CALIBRE_DB) {
            $sourcePath = $dbPath . DIRECTORY_SEPARATOR . 'metadata.db';
            // Add the calibre books into the export file
            $localPath = 'books';
        } else {
            $sourcePath = $dbPath;
            // Add the epub files into the export file
            $localPath = $this->dbConfig['epub_path'];
        }
        if ($targetType == Workflow::JSON_FILES) {
            $fileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.json';
        } else {
            $fileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.csv';
        }
        // Open or create the export file
        $export = new Export($sourceType, $sourcePath, $targetType, $fileName, true);

        // Add the items into the export file
        $export->process($dbPath, $localPath);
        $errors = $export->getErrors();
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
        // Download export
        if ($this->request->get('download')) {
            $export->download();
            return null;
        }
        // Save export
        $export->saveToFile();
        $messages = $export->getMessages();
        $message = implode("<br />\n", $messages);
        // Display info
        return $message . '<br />';
    }
}
