<?php
/**
 * ImportHandler class
 */

namespace Marsender\EPubLoader\Handlers;

use Marsender\EPubLoader\ActionHandler;
use Marsender\EPubLoader\RequestHandler;
use Marsender\EPubLoader\Import\BookImport;
use Marsender\EPubLoader\Import\CsvImport;
use Marsender\EPubLoader\Import\JsonImport;
use Exception;

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
        $import = new CsvImport($calibreFileName, $createDb, $bookIdsFileName, $this->cacheDir);

        // Init csv file
        $fileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.csv';
        // Add the epub files from the import file
        [$message, $errors] = $import->loadFromPath($dbPath, $fileName);
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
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
        $bookIdsFileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_bookids.txt';
        // Open or create the database
        $import = new JsonImport($calibreFileName, $createDb, $bookIdsFileName, $this->cacheDir);

        // Add the json files into the database
        $jsonPath = $this->dbConfig['json_path'] ?? $this->dbConfig['epub_path'];
        [$message, $errors] = $import->loadFromPath($dbPath, $jsonPath);
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
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
        $import = new BookImport($calibreFileName, $createDb, $bookIdsFileName, $this->cacheDir);
        // Add the epub files into the database
        $epubPath = $this->dbConfig['epub_path'];
        [$message, $errors] = $import->loadFromPath($dbPath, $epubPath);
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
        // Display info
        return $message . '<br />';
    }
}
