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

use Marsender\EPubLoader\Metadata\BookInfos;
use Exception;

abstract class SourceExport
{
    public const EXPORT_TYPE_CSV = 1;

    protected string $mLabel = 'Export ebooks to';
    /** @var mixed */
    protected $mTarget = null;
    protected int $mNbBook = 0;
    protected string $mFileName = '';

    /**
     * Open an export file (or create if file does not exist)
     *
     * @param string $inFileName Export file name
     * @param integer $inExportType Export type
     * @param boolean $inCreate Force file creation
     * @throws Exception if error
     */
    public function __construct($inFileName, $inExportType, $inCreate = false)
    {
        $this->mFileName = $inFileName;
        switch ($inExportType) {
            case self::EXPORT_TYPE_CSV:
                $this->mTarget = new ExportCsvFile($inFileName, $inCreate);
                break;
            default:
                $error = sprintf('Incorrect export type: %d', $inExportType);
                throw new Exception($error);
        }
    }

    /**
     * Load books from <something> in path
     *
     * @param string $inBasePath base directory
     * @param string $localPath relative to $inBasePath
     *
     * @return array{string, array<mixed>}
     */
    abstract public function loadFromPath($inBasePath, $localPath);

    /**
     * Summary of getBookId
     * @param string $inBookFileName
     * @return int
     */
    public function getBookId($inBookFileName)
    {
        return 0;
    }

    /**
     * Add a new book to the export
     *
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $inBookId Book id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     *
     * @return void
     */
    protected function addBook($inBookInfo, $inBookId = 0)
    {
        $this->mTarget->addBook($inBookInfo, $inBookId);
    }

    /**
     * Download export and stop further script execution
     * @return void
     */
    public function download()
    {
        $this->mTarget->download();
    }

    /**
     * Save export to file
     * @return void
     */
    public function saveToFile()
    {
        $this->mTarget->saveToFile();
    }
}
