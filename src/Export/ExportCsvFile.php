<?php
/**
 * ExportCsvFile class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Export;

use Marsender\EPubLoader\Metadata\BookInfos;
use Exception;

class ExportCsvFile extends ExportTarget
{
    /** @var array<string>|null */
    protected $mLines = null;
    protected int $mNbBook = 0;

    public const CSV_SEPARATOR = "\t";

    /**
     * Open an export file (or create if file does not exist)
     *
     * @param string $inFileName Export file name
     * @param boolean $inCreate Force file creation
     */
    public function __construct($inFileName, $inCreate = false)
    {
        $this->mSearch = ["\r", "\n", self::CSV_SEPARATOR];
        $this->mReplace = ['', '<br />', ''];

        // Init container
        $this->mLines = [];

        parent::__construct($inFileName, $inCreate);
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
    public function addBook($inBookInfo, $inBookId = 0): void
    {
        // Add export header
        if ($this->mNbBook++ == 0) {
            $i = 1;
            $this->setProperty($i++, 'Format');
            $this->setProperty($i++, 'Path');
            $this->setProperty($i++, 'Name');
            $this->setProperty($i++, 'Uuid');
            $this->setProperty($i++, 'Uri');
            $this->setProperty($i++, 'Title');
            $this->setProperty($i++, 'Authors');
            $this->setProperty($i++, 'AuthorsSort');
            $this->setProperty($i++, 'Language');
            $this->setProperty($i++, 'Description');
            $this->setProperty($i++, 'Subjects');
            $this->setProperty($i++, 'Cover');
            $this->setProperty($i++, 'Isbn');
            $this->setProperty($i++, 'Rights');
            $this->setProperty($i++, 'Publisher');
            $this->setProperty($i++, 'Serie');
            $this->setProperty($i++, 'SerieIndex');
            $this->setProperty($i++, 'CreationDate');
            $this->setProperty($i++, 'ModificationDate');
            $this->addContent();
        }

        // Add book infos to the export
        $i = 1;
        $this->setProperty($i++, $inBookInfo->mFormat);
        $this->setProperty($i++, $inBookInfo->mBasePath . DIRECTORY_SEPARATOR . $inBookInfo->mPath);
        $this->setProperty($i++, $inBookInfo->mName);
        $this->setProperty($i++, $inBookInfo->mUuid);
        $this->setProperty($i++, $inBookInfo->mUri);
        $this->setProperty($i++, $inBookInfo->mTitle);
        $this->setProperty($i++, implode(' - ', $inBookInfo->mAuthors));
        $this->setProperty($i++, implode(' - ', array_keys($inBookInfo->mAuthors)));
        $this->setProperty($i++, $inBookInfo->mLanguage);
        $this->setProperty($i++, $inBookInfo->mDescription);
        $this->setProperty($i++, implode(' - ', $inBookInfo->mSubjects));
        $this->setProperty($i++, $inBookInfo->mCover);
        $this->setProperty($i++, $inBookInfo->mIsbn);
        $this->setProperty($i++, $inBookInfo->mRights);
        $this->setProperty($i++, $inBookInfo->mPublisher);
        $this->setProperty($i++, $inBookInfo->mSerie);
        $this->setProperty($i++, $inBookInfo->mSerieIndex);
        $this->setProperty($i++, $inBookInfo->mCreationDate);
        $this->setProperty($i++, $inBookInfo->mModificationDate);

        $this->addContent();
    }

    /**
     * Add the current properties into the export content
     * and reset the properties
     * @return void
     */
    public function addContent()
    {
        $text = '';
        foreach ($this->mProperties as $key => $value) {
            $info = '';
            if (is_array($value)) {
                foreach ($value as $value1) {
                    // Escape quotes
                    if (str_contains((string) $value1, '\'')) {
                        $value1 = '\'' . str_replace('\'', '\'\'', $value1) . '\'';
                    }
                    $text .= $value1 . self::CSV_SEPARATOR;
                }
                continue;
            } else {
                // Escape quotes
                if (str_contains((string) $value, '\'')) {
                    $value = '\'' . str_replace('\'', '\'\'', $value) . '\'';
                }
                $info = $value;
            }
            $text .= $info . self::CSV_SEPARATOR;
        }

        $this->mLines[] = $text;

        $this->clearProperties();
    }

    /**
     * Summary of GetContent
     * @return string
     */
    protected function getContent()
    {
        $text = implode("\n", $this->mLines) . "\n";

        return $text;
    }
}
