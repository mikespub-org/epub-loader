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
use Marsender\EPubLoader\Metadata\BookInfos;
use Exception;

abstract class SourceImport
{
    public const IMPORT_TYPE_CALIBRE = 1;

    protected string $mLabel = 'Load database';
    /** @var mixed */
    protected $mTarget = null;
    protected int $mNbBook = 0;
    protected string $mFileName = '';

    /**
     * Open an import database (or create if file does not exist)
     *
     * @param string $inDbFileName Calibre database file name
     * @param boolean $inCreate Force database creation
     * @param string $inBookIdsFileName File name containing a map of file names to calibre book ids
     * @throws Exception if error
     */
    public function __construct($inDbFileName, $inCreate = false, $inBookIdsFileName = '')
    {
        $this->mFileName = $inDbFileName;
        // @todo support other import targets beside Calibre?
        $this->mTarget = new ImportCalibre($inDbFileName, $inCreate, $inBookIdsFileName);
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
        return $this->mTarget->getBookId($inBookFileName);
    }

    /**
     * Add a new book to the import
     *
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $inBookId Book id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     *
     * @return void
     */
    protected function addBook($inBookInfo, $inBookId = 0): void
    {
        $this->mTarget->addBook($inBookInfo, $inBookId);
    }
}
