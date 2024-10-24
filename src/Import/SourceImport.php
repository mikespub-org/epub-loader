<?php
/**
 * SourceImport class --> ImportTarget
 * |-> BookImport         |-> ImportCalibre (IMPORT_TYPE_CALIBRE)
 * |-> CsvImport          |-> ...
 * |-> JsonImport         implement addBook()
 * |-> ...
 * implement loadFromPath()
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Import\ImportCalibre;
use Marsender\EPubLoader\Metadata\BookInfo;
use Exception;

abstract class SourceImport
{
    public const IMPORT_TYPE_CALIBRE = 1;

    protected string $label = 'Load database';
    /** @var mixed */
    protected $target = null;
    protected int $nbBook = 0;
    protected string $fileName = '';

    /**
     * Open an import database (or create if file does not exist)
     *
     * @param string $dbFileName Calibre database file name
     * @param boolean $create Force database creation
     * @param string $bookIdsFileName File name containing a map of file names to calibre book ids
     * @throws Exception if error
     */
    public function __construct($dbFileName, $create = false, $bookIdsFileName = '')
    {
        $this->fileName = $dbFileName;
        // @todo support other import targets beside Calibre?
        $this->target = new ImportCalibre($dbFileName, $create, $bookIdsFileName);
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
        return $this->target->getBookId($bookFileName);
    }

    /**
     * Add a new book to the import
     *
     * @param BookInfo $bookInfo BookInfo object
     * @param int $bookId Book id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     *
     * @return void
     */
    protected function addBook($bookInfo, $bookId = 0): void
    {
        $this->target->addBook($bookInfo, $bookId);
    }
}
