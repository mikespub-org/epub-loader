<?php
/**
 * ImportHandler class
 */

namespace Marsender\EPubLoader\Handlers;

use Marsender\EPubLoader\ActionHandler;
use Marsender\EPubLoader\RequestHandler;
use Marsender\EPubLoader\Workflows\Import;
use Marsender\EPubLoader\Workflows\Workflow;

class ImportHandler extends ActionHandler
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
            case 'csv_import':
                $createDb = $this->dbConfig['create_db'];
                $result = $this->csv_import($createDb);
                break;
            case 'json_import':
                $createDb = $this->dbConfig['create_db'];
                $result = $this->json_import($createDb);
                break;
            case 'cache_load':
                $createDb = $this->dbConfig['create_db'];
                $result = $this->cache_load($createDb);
                break;
            case 'db_load':
                $createDb = $this->dbConfig['create_db'];
                $result = $this->db_load($createDb);
                break;
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of csv_import - @todo fix calibreFileName avoiding overlap with existing metadata.db
     * @param bool $createDb
     * @return string
     */
    public function csv_import($createDb = false)
    {
        // Init database file
        $dbPath = $this->dbConfig['db_path'];
        $calibreFileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.db';
        $bookIdsFileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_bookids.txt';
        // Open or create the database
        $sourceType = Workflow::CSV_FILES;
        $import = new Import($sourceType, $calibreFileName, $createDb, $bookIdsFileName, $this->cacheDir);

        // Init csv file
        $fileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.csv';
        // Add the epub files from the import file
        $import->process($dbPath, $fileName);
        $errors = $import->getErrors();
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
        $messages = $import->getMessages();
        $message = implode("<br />\n", $messages);
        // Display info
        return $message . '<br />';
    }

    /**
     * Summary of json_import - @todo fix calibreFileName avoiding overlap with existing metadata.db
     * @param bool $createDb
     * @return string
     */
    public function json_import($createDb = false)
    {
        // Init database file
        $dbPath = $this->dbConfig['db_path'];
        $calibreFileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.db';
        //$bookIdsFileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_bookids.txt';
        $bookIdsFileName = '';
        // Open or create the database
        $sourceType = Workflow::JSON_FILES;
        $import = new Import($sourceType, $calibreFileName, $createDb, $bookIdsFileName, $this->cacheDir);

        // Init json path
        $jsonPath = '.';
        // Add the epub files from the import file(s)
        $import->process($dbPath, $jsonPath);
        $errors = $import->getErrors();
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
        $messages = $import->getMessages();
        $message = implode("<br />\n", $messages);
        // Display info
        return $message . '<br />';
    }

    /**
     * Summary of cache_load - @todo fix calibreFileName avoiding overlap with existing metadata.db
     * @param bool $createDb
     * @return string
     */
    public function cache_load($createDb = false)
    {
        // Init database file
        $dbPath = $this->dbConfig['db_path'];
        $calibreFileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.db';
        $bookIdsFileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_bookids.txt';
        // Open or create the database
        $sourceType = Workflow::JSON_FILES;
        $import = new Import($sourceType, $calibreFileName, $createDb, $bookIdsFileName, $this->cacheDir);

        // Add the json files into the database
        $jsonPath = $this->dbConfig['json_path'] ?? $this->dbConfig['epub_path'];
        $import->process($dbPath, $jsonPath);
        $errors = $import->getErrors();
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
        $messages = $import->getMessages();
        $message = implode("<br />\n", $messages);
        // Display info
        return $message . '<br />';
    }

    /**
     * Summary of db_load
     * @param bool $createDb
     * @return string
     */
    public function db_load($createDb = false)
    {
        // Init database file
        $dbPath = $this->dbConfig['db_path'];
        $calibreFileName = $dbPath . DIRECTORY_SEPARATOR . 'metadata.db';
        $bookIdsFileName = $dbPath . DIRECTORY_SEPARATOR . 'bookids.txt';
        // Open or create the database
        $sourceType = Workflow::LOCAL_BOOKS;
        $import = new Import($sourceType, $calibreFileName, $createDb, $bookIdsFileName, $this->cacheDir);

        // Add the epub files into the database
        $epubPath = $this->dbConfig['epub_path'];
        $import->process($dbPath, $epubPath);
        $errors = $import->getErrors();
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
        $messages = $import->getMessages();
        $message = implode("<br />\n", $messages);
        // Display info
        return $message . '<br />';
    }
}
