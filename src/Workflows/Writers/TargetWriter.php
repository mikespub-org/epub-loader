<?php
/**
 * TargetWriter class
 * |-> CalibreWriter (CALIBRE_DB)
 * |-> CsvFileWriter (CSV_FILES)
 * |-> JsonFileWriter (JSON_FILES)
 * |-> ...
 * implement addBook()
 */

namespace Marsender\EPubLoader\Workflows\Writers;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Exception;

abstract class TargetWriter
{
    /** @var array<mixed> */
    public array $messages = [];
    /** @var array<mixed> */
    public array $errors = [];

    /**
     * Add a new book to the target (must be implemented)
     *
     * @param BookInfo $bookInfo BookInfo object
     * @param int $bookId Book id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     *
     * @return void
     */
    abstract public function addBook($bookInfo, $bookId = 0);

    /**
     * Add a new author to the target (optional)
     *
     * @param AuthorInfo $authorInfo AuthorInfo object
     * @param mixed $authorId Author id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     *
     * @return void
     */
    public function addAuthor($authorInfo, $authorId = 0)
    {
        return;
    }

    /**
     * Add a new series to the target (optional)
     *
     * @param SeriesInfo $seriesInfo SeriesInfo object
     * @param mixed $seriesId Series id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     *
     * @return void
     */
    public function addSeries($seriesInfo, $seriesId = 0)
    {
        return;
    }

    /**
     * Summary of addMessage
     * @param string $source
     * @param mixed $message
     * @return void
     */
    public function addMessage($source, $message)
    {
        $this->messages[$source] = $message;
    }

    /**
     * Summary of addError
     * @param string $source
     * @param mixed $error
     * @return void
     */
    public function addError($source, $error)
    {
        $this->errors[$source] = $error;
    }
}
