<?php
/**
 * CalibreReader class
 */

namespace Marsender\EPubLoader\Workflows\Readers;

use Marsender\EPubLoader\CalibreDbLoader;
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;

class CalibreReader extends DatabaseReader
{
    /** @var CalibreDbLoader */
    protected $db;

    /**
     * Open a Calibre database file
     *
     * @param string $dbFileName Calibre database file name
     */
    public function __construct($dbFileName)
    {
        $this->dbFileName = $dbFileName;
        $this->db = new CalibreDbLoader($dbFileName);
        $this->db->getNotesDb();
    }

    /**
     * Load books from <something> in path
     *
     * @param string $basePath base directory
     * @param string $tableName item type to return
     *
     * @return \Generator<int, BookInfo|AuthorInfo|SeriesInfo>
     */
    public function iterate($basePath, $tableName)
    {
        foreach ($this->db->getBooks() as $bookId => $data) {
            $bookInfo = BookInfo::load($basePath, $data, $this->db);
            yield $bookId => $bookInfo;
        }
        // @todo loop over database to load BookInfo and add books
        $message = $tableName . ': TODO';
        $this->addMessage($tableName, $message);
    }
}
