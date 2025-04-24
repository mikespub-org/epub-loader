<?php

/**
 * DatabaseReader class
 */

namespace Marsender\EPubLoader\Workflows\Readers;

use Marsender\EPubLoader\DatabaseLoader;
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;

abstract class DatabaseReader extends SourceReader
{
    /** @var DatabaseLoader */
    protected $dbLoader;
    protected string $dbFileName;

    /**
     * Open a database file
     *
     * @param string $dbFileName database file name
     */
    public function __construct($dbFileName)
    {
        $this->dbFileName = $dbFileName;
        $this->dbLoader = new DatabaseLoader($dbFileName);
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
        yield 0 => new BookInfo();
        // @todo loop over database to load BookInfo and add books
        $message = 'TODO';
        $this->addMessage($tableName, $message);
    }
}
