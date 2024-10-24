<?php
/**
 * ImportTarget class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\BookInfo;
use PDO;
use Exception;

abstract class ImportTarget
{
    /**
     * Calibre database sql file that comes unmodified from Calibre project:
     * https://raw.githubusercontent.com/kovidgoyal/calibre/master/resources/metadata_sqlite.sql
     */
    protected static string $createDbSql = 'schema/metadata_sqlite.sql';

    /** @var PDO|null */
    protected $db = null;
    /** @var string|null */
    protected $fileName = null;
    /** @var array<string, mixed>|null */
    protected $bookId = null;
    protected string $bookIdFileName = '';

    /**
     * Open a Calibre database (or create if database does not exist)
     *
     * @param string $dbFileName Calibre database file name
     * @param boolean $create Force database creation
     * @param string $bookIdsFileName File name containing a map of file names to calibre book ids
     */
    public function __construct($dbFileName, $create = false, $bookIdsFileName = '')
    {
        $this->fileName = $dbFileName;
        if ($create) {
            $this->createDatabase($dbFileName);
            if (!empty($bookIdsFileName)) {
                $this->loadBookIds($bookIdsFileName);
            }
        } else {
            $this->openDatabase($dbFileName);
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
     * @param BookInfo $bookInfo BookInfo object
     * @param int $bookId Book id in the calibre db (or 0 for auto incrementation)
     * @param string $sortField Add 'calibre_database_field_sort' field for tags
     *
     * @throws Exception if error
     *
     * @return void
     */
    abstract public function addBook($bookInfo, $bookId, $sortField = 'sort');

    /**
     * Create an sqlite database
     *
     * @param string $dbFileName Database file name
     *
     * @throws Exception if error
     *
     * @return void
     */
    protected function createDatabase($dbFileName)
    {
        $sqlFileName = dirname(__DIR__, 2) . '/' . static::$createDbSql;
        // Read the sql file
        $content = file_get_contents($sqlFileName);
        if ($content === false) {
            $error = sprintf('Cannot read sql file: %s', $sqlFileName);
            throw new Exception($error);
        }

        // Remove the database file
        if (file_exists($dbFileName) && !unlink($dbFileName)) {
            $error = sprintf('Cannot remove database file: %s', $dbFileName);
            throw new Exception($error);
        }

        // Create the new database file
        $this->openDatabase($dbFileName);

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
                $stmt = $this->db->prepare($sql);
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
     * @param string $dbFileName Database file name
     * @throws Exception if error
     *
     * @return void
     */
    protected function openDatabase($dbFileName)
    {
        try {
            // Init the Data Source Name
            $dsn = 'sqlite:' . $dbFileName;
            // Open the database
            $this->db = new PDO($dsn); // Send an exception if error
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec('pragma synchronous = off');
        } catch (Exception $e) {
            $error = sprintf('Cannot open database [%s]: %s', $dsn, $e->getMessage());
            throw new Exception($error);
        }
    }

    /**
     * Load the book ids map in order to reuse calibe book id when recreating database
     *
     * @param string $bookIdsFileName File name containing a map of file names to calibre book ids
     *
     * @return void
     */
    protected function loadBookIds($bookIdsFileName)
    {
        $this->bookId = [];
        $this->bookIdFileName = $bookIdsFileName;

        if (empty($this->bookIdFileName) || !file_exists($this->bookIdFileName)) {
            return;
        }

        // Load the book ids file
        $lines = file($this->bookIdFileName);
        foreach ($lines as $line) {
            $tab = explode("\t", trim($line));
            if (count($tab) != 2) {
                continue;
            }
            $this->bookId[$tab[0]] = (int) $tab[1];
        }
    }

    /**
     * Save the book ids file
     * @return void
     */
    protected function saveBookIds()
    {
        if (empty($this->bookIdFileName)) {
            return;
        }

        $tab = [];
        foreach ($this->bookId as $key => $value) {
            $tab[] = sprintf('%s%s%d', $key, "\t", $value);
        }

        file_put_contents($this->bookIdFileName, implode("\n", $tab) . "\n");
    }

    /**
     * Summary of getBookId
     * @param string $bookFileName
     * @return int
     */
    public function getBookId($bookFileName)
    {
        if (isset($this->bookId[$bookFileName])) {
            $res = (int) $this->bookId[$bookFileName];
        } else {
            // Get max book id
            $res = 0;
            foreach ($this->bookId as $key => $value) {
                if ($value > $res) {
                    $res = $value;
                }
            }
            $res++;
            $this->bookId[$bookFileName] = $res;
        }

        return $res;
    }
}
