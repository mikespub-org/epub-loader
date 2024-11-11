<?php
/**
 * JsonFileWriter class
 */

namespace Marsender\EPubLoader\Workflows\Writers;

use Marsender\EPubLoader\Models\BookInfo;
use Exception;

class JsonFileWriter extends FileWriter
{
    /** @var array<mixed>|null */
    protected $books = null;
    protected int $nbBook = 0;

    /**
     * Open an export file (or create if file does not exist)
     *
     * @param string $fileName Export file name
     * @param boolean $create Force file creation
     */
    public function __construct($fileName, $create = false)
    {
        // Init container
        $this->books = [];
        $this->nbBook = 0;

        parent::__construct($fileName, $create);
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
    public function addBook($bookInfo, $bookId = 0): void
    {
        $this->nbBook++;
        $bookId = $bookId ?: $this->nbBook;
        $this->books[$bookId] = $bookInfo;
    }

    /**
     * Summary of getContent
     * @return string
     */
    protected function getContent()
    {
        $text = json_encode($this->books, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $text;
    }
}
