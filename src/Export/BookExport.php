<?php
/**
 * BookExport class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 */

namespace Marsender\EPubLoader\Export;

use Marsender\EPubLoader\Metadata\BookInfos;
use Exception;

class BookExport
{
    /** @var mixed */
    protected $mExport = null;
    protected int $mNbBook = 0;

    public const eExportTypeCsv = 1;
    public const CsvSeparator = "\t";

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
        switch ($inExportType) {
            case static::eExportTypeCsv:
                $this->mExport = new CsvExport($inFileName, $inCreate);
                break;
            default:
                $error = sprintf('Incorrect export type: %d', $inExportType);
                throw new Exception($error);
        }
    }

    /**
     * Add an epub to the export
     *
     * @param string $inBasePath Epub base directory
     * @param string $inFileName Epub file name (from base directory)
     * @throws Exception if error
     *
     * @return string Empty string or error if any
     */
    public function AddEpub($inBasePath, $inFileName)
    {
        $error = '';

        try {
            // Load the book infos
            $bookInfos = new BookInfos();
            $bookInfos->LoadFromEpub($inBasePath, $inFileName);
            // Add the book
            $this->AddBook($bookInfos);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return $error;
    }

    /**
     * Add a new book to the export
     *
     * @param BookInfos $inBookInfo BookInfo object
     * @throws Exception if error
     *
     * @return void
     */
    protected function AddBook($inBookInfo)
    {
        // Add export header
        if ($this->mNbBook++ == 0) {
            $i = 1;
            $this->mExport->SetProperty($i++, 'Format');
            $this->mExport->SetProperty($i++, 'Path');
            $this->mExport->SetProperty($i++, 'Name');
            $this->mExport->SetProperty($i++, 'Uuid');
            $this->mExport->SetProperty($i++, 'Uri');
            $this->mExport->SetProperty($i++, 'Title');
            $this->mExport->SetProperty($i++, 'Authors');
            $this->mExport->SetProperty($i++, 'AuthorsSort');
            $this->mExport->SetProperty($i++, 'Language');
            $this->mExport->SetProperty($i++, 'Description');
            $this->mExport->SetProperty($i++, 'Subjects');
            $this->mExport->SetProperty($i++, 'Cover');
            $this->mExport->SetProperty($i++, 'Isbn');
            $this->mExport->SetProperty($i++, 'Rights');
            $this->mExport->SetProperty($i++, 'Publisher');
            $this->mExport->SetProperty($i++, 'Serie');
            $this->mExport->SetProperty($i++, 'SerieIndex');
            $this->mExport->SetProperty($i++, 'CreationDate');
            $this->mExport->SetProperty($i++, 'ModificationDate');
            $this->mExport->AddContent();
        }

        // Add book infos to the export
        $i = 1;
        $this->mExport->SetProperty($i++, $inBookInfo->mFormat);
        $this->mExport->SetProperty($i++, $inBookInfo->mBasePath . DIRECTORY_SEPARATOR . $inBookInfo->mPath);
        $this->mExport->SetProperty($i++, $inBookInfo->mName);
        $this->mExport->SetProperty($i++, $inBookInfo->mUuid);
        $this->mExport->SetProperty($i++, $inBookInfo->mUri);
        $this->mExport->SetProperty($i++, $inBookInfo->mTitle);
        $this->mExport->SetProperty($i++, implode(' - ', $inBookInfo->mAuthors));
        $this->mExport->SetProperty($i++, implode(' - ', array_keys($inBookInfo->mAuthors)));
        $this->mExport->SetProperty($i++, $inBookInfo->mLanguage);
        $this->mExport->SetProperty($i++, $inBookInfo->mDescription);
        $this->mExport->SetProperty($i++, implode(' - ', $inBookInfo->mSubjects));
        $this->mExport->SetProperty($i++, $inBookInfo->mCover);
        $this->mExport->SetProperty($i++, $inBookInfo->mIsbn);
        $this->mExport->SetProperty($i++, $inBookInfo->mRights);
        $this->mExport->SetProperty($i++, $inBookInfo->mPublisher);
        $this->mExport->SetProperty($i++, $inBookInfo->mSerie);
        $this->mExport->SetProperty($i++, $inBookInfo->mSerieIndex);
        $this->mExport->SetProperty($i++, $inBookInfo->mCreationDate);
        $this->mExport->SetProperty($i++, $inBookInfo->mModificationDate);

        $this->mExport->AddContent();
    }

    /**
     * Download export and stop further script execution
     * @return void
     */
    public function Download()
    {
        $this->mExport->Download();
    }

    /**
     * Save export to file
     * @return void
     */
    public function SaveToFile()
    {
        $this->mExport->SaveToFile();
    }
}
