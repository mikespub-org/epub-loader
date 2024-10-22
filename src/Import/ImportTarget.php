<?php
/**
 * ImportTarget class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\BookInfos;
use PDO;
use Exception;

abstract class ImportTarget
{
    /**
     * Calibre database sql file that comes unmodified from Calibre project:
     * https://raw.githubusercontent.com/kovidgoyal/calibre/master/resources/metadata_sqlite.sql
     */
    protected static string $mCreateDbSql = 'schema/metadata_sqlite.sql';

    /** @var PDO|null */
    protected $mDb = null;
    /** @var string|null */
    protected $mFileName = null;
    /** @var array<string, mixed>|null */
    protected $mBookId = null;
    protected string $mBookIdFileName = '';

    /**
     * Open a Calibre database (or create if database does not exist)
     *
     * @param string $inDbFileName Calibre database file name
     * @param boolean $inCreate Force database creation
     * @param string $inBookIdsFileName File name containing a map of file names to calibre book ids
     */
    public function __construct($inDbFileName, $inCreate = false, $inBookIdsFileName = '')
    {
        $this->mFileName = $inDbFileName;
        if ($inCreate) {
            $this->createDatabase($inDbFileName);
            if (!empty($inBookIdsFileName)) {
                $this->loadBookIds($inBookIdsFileName);
            }
        } else {
            $this->openDatabase($inDbFileName);
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->saveBookIds();
    }

    /**
     * Add a new book into the db
     *
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $inBookId Book id in the calibre db (or 0 for auto incrementation)
     * @param string $sortField Add 'calibre_database_field_sort' field for tags
     *
     * @throws Exception if error
     *
     * @return void
     */
    abstract public function addBook($inBookInfo, $inBookId, $sortField = 'sort');

    /**
     * Create an sqlite database
     *
     * @param string $inDbFileName Database file name
     *
     * @throws Exception if error
     *
     * @return void
     */
    protected function createDatabase($inDbFileName)
    {
        $sqlFileName = dirname(__DIR__, 2) . '/' . static::$mCreateDbSql;
        // Read the sql file
        $content = file_get_contents($sqlFileName);
        if ($content === false) {
            $error = sprintf('Cannot read sql file: %s', $sqlFileName);
            throw new Exception($error);
        }

        // Remove the database file
        if (file_exists($inDbFileName) && !unlink($inDbFileName)) {
            $error = sprintf('Cannot remove database file: %s', $inDbFileName);
            throw new Exception($error);
        }

        // Create the new database file
        $this->openDatabase($inDbFileName);

        // Create the database tables
        try {
            $sqlArray = explode('CREATE ', $content);
            foreach ($sqlArray as $sql) {
                $sql = trim($sql);
                if (empty($sql)) {
                    continue;
                }
                $sql = 'CREATE ' . $sql;
                $str = strtolower($sql);
                if (str_contains($str, 'create view')) {
                    continue;
                }
                if (str_contains($str, 'title_sort')) {
                    continue;
                }
                if (str_contains($str, 'fts5')) {
                    continue;
                }
                // Add 'calibre_database_field_cover' field for books
                if (str_contains($sql, 'has_cover BOOL DEFAULT 0,')) {
                    $sql = str_replace('has_cover BOOL DEFAULT 0,', 'has_cover BOOL DEFAULT 0,' . ' cover TEXT NOT NULL DEFAULT "",', $sql);
                }
                // Add 'calibre_database_field_sort' field for tags
                if (str_contains($sql, 'CREATE TABLE tags ')) {
                    $sql = str_replace('name TEXT NOT NULL COLLATE NOCASE,', 'name TEXT NOT NULL COLLATE NOCASE,' . ' sort TEXT COLLATE NOCASE,', $sql);
                }
                $stmt = $this->mDb->prepare($sql);
                $stmt->execute();
            }
        } catch (Exception $e) {
            $error = sprintf('Cannot create database: %s', $e->getMessage());
            throw new Exception($error);
        }
    }

    /**
     * Open an sqlite database
     *
     * @param string $inDbFileName Database file name
     * @throws Exception if error
     *
     * @return void
     */
    protected function openDatabase($inDbFileName)
    {
        try {
            // Init the Data Source Name
            $dsn = 'sqlite:' . $inDbFileName;
            // Open the database
            $this->mDb = new PDO($dsn); // Send an exception if error
            $this->mDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->mDb->exec('pragma synchronous = off');
        } catch (Exception $e) {
            $error = sprintf('Cannot open database [%s]: %s', $dsn, $e->getMessage());
            throw new Exception($error);
        }
    }

    /**
     * Load the book ids map in order to reuse calibe book id when recreating database
     *
     * @param string $inBookIdsFileName File name containing a map of file names to calibre book ids
     *
     * @return void
     */
    protected function loadBookIds($inBookIdsFileName)
    {
        $this->mBookId = [];
        $this->mBookIdFileName = $inBookIdsFileName;

        if (empty($this->mBookIdFileName) || !file_exists($this->mBookIdFileName)) {
            return;
        }

        // Load the book ids file
        $lines = file($this->mBookIdFileName);
        foreach ($lines as $line) {
            $tab = explode("\t", trim($line));
            if (count($tab) != 2) {
                continue;
            }
            $this->mBookId[$tab[0]] = (int) $tab[1];
        }
    }

    /**
     * Save the book ids file
     * @return void
     */
    protected function saveBookIds()
    {
        if (empty($this->mBookIdFileName)) {
            return;
        }

        $tab = [];
        foreach ($this->mBookId as $key => $value) {
            $tab[] = sprintf('%s%s%d', $key, "\t", $value);
        }

        file_put_contents($this->mBookIdFileName, implode("\n", $tab) . "\n");
    }

    /**
     * Summary of getBookId
     * @param string $inBookFileName
     * @return int
     */
    public function getBookId($inBookFileName)
    {
        if (isset($this->mBookId[$inBookFileName])) {
            $res = (int) $this->mBookId[$inBookFileName];
        } else {
            // Get max book id
            $res = 0;
            foreach ($this->mBookId as $key => $value) {
                if ($value > $res) {
                    $res = $value;
                }
            }
            $res++;
            $this->mBookId[$inBookFileName] = $res;
        }

        return $res;
    }
}
