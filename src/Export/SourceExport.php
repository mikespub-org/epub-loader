<?php
/**
 * SourceExport class --> ExportTarget
 * |-> BookExport         |-> ExportCsvFile (EXPORT_TYPE_CSV)
 * |-> CalibreExport      |-> ...
 * |-> ...                implement addBook()
 * implement loadFromPath()
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Export;

use Marsender\EPubLoader\Metadata\BookInfo;
use Exception;

abstract class SourceExport
{
    public const EXPORT_TYPE_CSV = 1;

    protected string $label = 'Export ebooks to';
    /** @var mixed */
    protected $target = null;
    protected int $nbBook = 0;
    protected string $fileName = '';

    /**
     * Open an export file (or create if file does not exist)
     *
     * @param string $fileName Export file name
     * @param integer $exportType Export type
     * @param boolean $create Force file creation
     * @throws Exception if error
     */
    public function __construct($fileName, $exportType, $create = false)
    {
        $this->fileName = $fileName;
        switch ($exportType) {
            case self::EXPORT_TYPE_CSV:
                $this->target = new ExportCsvFile($fileName, $create);
                break;
            default:
                $error = sprintf('Incorrect export type: %d', $exportType);
                throw new Exception($error);
        }
    }

    /**
     * Load books from <something> in path
     *
     * @param string $basePath base directory
     * @param string $localPath relative to $basePath
     *
     * @return array{string, array<mixed>}
     */
    abstract public function loadFromPath($basePath, $localPath);

    /**
     * Summary of getBookId
     * @param string $bookFileName
     * @return int
     */
    public function getBookId($bookFileName)
    {
        return 0;
    }

    /**
     * Add a new book to the export
     *
     * @param BookInfo $bookInfo BookInfo object
     * @param int $bookId Book id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     *
     * @return void
     */
    protected function addBook($bookInfo, $bookId = 0)
    {
        $this->target->addBook($bookInfo, $bookId);
    }

    /**
     * Download export and stop further script execution
     * @return void
     */
    public function download()
    {
        $this->target->download();
    }

    /**
     * Save export to file
     * @return void
     */
    public function saveToFile()
    {
        $this->target->saveToFile();
    }
}
