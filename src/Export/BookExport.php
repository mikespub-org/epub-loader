<?php
/**
 * BookExport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Export;

use Marsender\EPubLoader\Metadata\BookInfos;
use Marsender\EPubLoader\Metadata\BookLoadTrait;
use Exception;

class BookExport
{
    use BookLoadTrait;

    public const EXPORT_TYPE_CSV = 1;

    protected string $mLabel = 'Export ebooks to';
    /** @var mixed */
    protected $mExport = null;
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
                $this->mExport = new CsvExport($inFileName, $inCreate);
                break;
            default:
                $error = sprintf('Incorrect export type: %d', $inExportType);
                throw new Exception($error);
        }
    }

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
     * @see \Marsender\EPubLoader\Import\CsvImport::loadFromArray()
     *
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $inBookId Book id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     *
     * @return void
     */
    protected function addBook($inBookInfo, $inBookId = 0): void
    {
        // Add export header
        if ($this->mNbBook++ == 0) {
            $i = 1;
            $this->mExport->setProperty($i++, 'Format');
            $this->mExport->setProperty($i++, 'Path');
            $this->mExport->setProperty($i++, 'Name');
            $this->mExport->setProperty($i++, 'Uuid');
            $this->mExport->setProperty($i++, 'Uri');
            $this->mExport->setProperty($i++, 'Title');
            $this->mExport->setProperty($i++, 'Authors');
            $this->mExport->setProperty($i++, 'AuthorsSort');
            $this->mExport->setProperty($i++, 'Language');
            $this->mExport->setProperty($i++, 'Description');
            $this->mExport->setProperty($i++, 'Subjects');
            $this->mExport->setProperty($i++, 'Cover');
            $this->mExport->setProperty($i++, 'Isbn');
            $this->mExport->setProperty($i++, 'Rights');
            $this->mExport->setProperty($i++, 'Publisher');
            $this->mExport->setProperty($i++, 'Serie');
            $this->mExport->setProperty($i++, 'SerieIndex');
            $this->mExport->setProperty($i++, 'CreationDate');
            $this->mExport->setProperty($i++, 'ModificationDate');
            $this->mExport->addContent();
        }

        // Add book infos to the export
        $i = 1;
        $this->mExport->setProperty($i++, $inBookInfo->mFormat);
        $this->mExport->setProperty($i++, $inBookInfo->mBasePath . DIRECTORY_SEPARATOR . $inBookInfo->mPath);
        $this->mExport->setProperty($i++, $inBookInfo->mName);
        $this->mExport->setProperty($i++, $inBookInfo->mUuid);
        $this->mExport->setProperty($i++, $inBookInfo->mUri);
        $this->mExport->setProperty($i++, $inBookInfo->mTitle);
        $this->mExport->setProperty($i++, implode(' - ', $inBookInfo->mAuthors));
        $this->mExport->setProperty($i++, implode(' - ', array_keys($inBookInfo->mAuthors)));
        $this->mExport->setProperty($i++, $inBookInfo->mLanguage);
        $this->mExport->setProperty($i++, $inBookInfo->mDescription);
        $this->mExport->setProperty($i++, implode(' - ', $inBookInfo->mSubjects));
        $this->mExport->setProperty($i++, $inBookInfo->mCover);
        $this->mExport->setProperty($i++, $inBookInfo->mIsbn);
        $this->mExport->setProperty($i++, $inBookInfo->mRights);
        $this->mExport->setProperty($i++, $inBookInfo->mPublisher);
        $this->mExport->setProperty($i++, $inBookInfo->mSerie);
        $this->mExport->setProperty($i++, $inBookInfo->mSerieIndex);
        $this->mExport->setProperty($i++, $inBookInfo->mCreationDate);
        $this->mExport->setProperty($i++, $inBookInfo->mModificationDate);

        $this->mExport->addContent();
    }

    /**
     * Download export and stop further script execution
     * @return void
     */
    public function download()
    {
        $this->mExport->download();
    }

    /**
     * Save export to file
     * @return void
     */
    public function saveToFile()
    {
        $this->mExport->saveToFile();
    }
}
