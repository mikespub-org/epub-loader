<?php
/**
 * ExportHandler class
 */

namespace Marsender\EPubLoader\Handlers;

use Marsender\EPubLoader\ActionHandler;
use Marsender\EPubLoader\Workflows\Export;
use Marsender\EPubLoader\Workflows\Workflow;
use Marsender\EPubLoader\RequestHandler;
use Marsender\EPubLoader\Metadata\BaseCache;
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
        return $this->doExport(Workflow::LOCAL_BOOKS);
    }

    /**
     * Summary of csv_dump
     * @return string|null
     */
    public function csv_dump()
    {
        return $this->doExport(Workflow::CALIBRE_DB);
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
        $sourceType = Workflow::JSON_FILES;
        $sourcePath = $this->cacheDir;
        $targetType = Workflow::CALLBACK;
        $targetPath = $result['callbacks'];
        $workflow = Workflow::getWorkflow($sourceType, $sourcePath, $targetType, $targetPath);
        if ($cacheName == 'googlebooks') {
            $cachePath = 'google';
            $jsonPath = $cachePath . '/' . $result['cacheType'];
        } elseif ($cacheName == 'openlibrary' && str_starts_with($result['cacheType'], 'entities/')) {
            $cachePath = $cacheName;
            $cacheType = 'entities';
            $jsonPath = $cachePath . '/' . $cacheType;
        } else {
            $cachePath = $cacheName;
            $jsonPath = $cachePath . '/' . $result['cacheType'];
        }
        // @todo get bookId etc. from somewhere
        $workflow->process($sourcePath, $jsonPath);
        $result['messages'] = $workflow->getMessages();
        $result['errors'] = $workflow->getErrors();
        return $result;
    }

    /**
     * Summary of do_export
     * @param int $sourceType Source type
     * @return string|null
     */
    protected function doExport($sourceType)
    {
        // Init csv file
        $dbPath = $this->dbConfig['db_path'];
        $fileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.csv';
        // Open or create the export file
        $targetType = Workflow::CSV_FILES;
        $export = new Export($sourceType, $dbPath, $targetType, $fileName, true);

        // Add the epub files into the export file
        $epubPath = $this->dbConfig['epub_path'];
        $export->process($dbPath, $epubPath);
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
        $export->SaveToFile();
        $messages = $export->getMessages();
        $message = implode("\n", $messages);
        // Display info
        return $message . '<br />';
    }
}
