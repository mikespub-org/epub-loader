<?php
/**
 * ExportHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Handlers;

use Marsender\EPubLoader\ActionHandler;
use Marsender\EPubLoader\RequestHandler;
use Marsender\EPubLoader\Export\BookExport;
use Marsender\EPubLoader\Export\SourceExport;
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
        // Init csv file
        $dbPath = $this->dbConfig['db_path'];
        $fileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.csv';
        // Open or create the export file
        $export = new BookExport($fileName, SourceExport::EXPORT_TYPE_CSV, true);
        // Add the epub files into the export file
        $epubPath = $this->dbConfig['epub_path'];
        [$message, $errors] = $export->loadFromPath($dbPath, $epubPath);
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
        // Display info
        return $message . '<br />';
    }
}
