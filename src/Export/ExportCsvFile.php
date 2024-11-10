<?php
/**
 * ExportCsvFile class
 */

namespace Marsender\EPubLoader\Export;

use Marsender\EPubLoader\Models\BookInfo;
use Exception;

class ExportCsvFile extends ExportTarget
{
    /** @var array<string>|null */
    protected $lines = null;
    protected int $nbBook = 0;

    public const CSV_SEPARATOR = "\t";

    /**
     * Open an export file (or create if file does not exist)
     *
     * @param string $fileName Export file name
     * @param boolean $create Force file creation
     */
    public function __construct($fileName, $create = false)
    {
        $this->search = ["\r", "\n", self::CSV_SEPARATOR];
        $this->replace = ['', '<br />', ''];

        // Init container
        $this->lines = [];

        parent::__construct($fileName, $create);
    }

    /**
     * Add a new book to the export
     * @see \Marsender\EPubLoader\Import\CsvImport::loadFromArray()
     *
     * @param BookInfo $bookInfo BookInfo object
     * @param int $bookId Book id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     *
     * @return void
     */
    public function addBook($bookInfo, $bookId = 0): void
    {
        // Add export header
        if ($this->nbBook++ == 0) {
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
        $this->setProperty($i++, $bookInfo->format);
        $this->setProperty($i++, $bookInfo->basePath . DIRECTORY_SEPARATOR . $bookInfo->path);
        $this->setProperty($i++, $bookInfo->id);
        $this->setProperty($i++, $bookInfo->uuid);
        $this->setProperty($i++, $bookInfo->uri);
        $this->setProperty($i++, $bookInfo->title);
        //$this->setProperty($i++, implode(' - ', $bookInfo->authors));
        //$this->setProperty($i++, implode(' - ', array_keys($bookInfo->authors)));
        $this->setProperty($i++, implode(' - ', $bookInfo->getAuthorNames()));
        $this->setProperty($i++, implode(' - ', $bookInfo->getAuthorSorts()));
        $this->setProperty($i++, $bookInfo->language);
        $this->setProperty($i++, $bookInfo->description);
        $this->setProperty($i++, implode(' - ', $bookInfo->subjects));
        $this->setProperty($i++, $bookInfo->cover);
        $this->setProperty($i++, $bookInfo->isbn);
        $this->setProperty($i++, $bookInfo->rights);
        $this->setProperty($i++, $bookInfo->publisher);
        //$this->setProperty($i++, $bookInfo->serie);
        //$this->setProperty($i++, $bookInfo->serieIndex);
        $seriesInfo = $bookInfo->getSeriesInfo();
        $this->setProperty($i++, $seriesInfo->title);
        $this->setProperty($i++, $seriesInfo->index);
        $this->setProperty($i++, $bookInfo->creationDate);
        $this->setProperty($i++, $bookInfo->modificationDate);

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
        foreach ($this->properties as $key => $value) {
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

        $this->lines[] = $text;

        $this->clearProperties();
    }

    /**
     * Summary of GetContent
     * @return string
     */
    protected function getContent()
    {
        $text = implode("\n", $this->lines) . "\n";

        return $text;
    }
}
