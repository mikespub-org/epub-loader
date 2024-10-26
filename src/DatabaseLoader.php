<?php
/**
 * DatabaseLoader class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

use Marsender\EPubLoader\Models\BaseInfo;
use PDO;
use Exception;

class DatabaseLoader
{
    /**
     * Calibre database sql file that comes unmodified from Calibre project:
     * https://raw.githubusercontent.com/kovidgoyal/calibre/master/resources/metadata_sqlite.sql
     */
    protected static string $createDbSql = 'schema/metadata_sqlite.sql';
    protected static string $notesDbSql = 'schema/notes_sqlite.sql';

    /** @var PDO|null */
    protected $db = null;
    /** @var string|null */
    protected $dbFileName = null;
    public bool $readOnly = false;

    /**
     * Open a database (or create if database does not exist)
     *
     * @param string $dbFileName Database file name
     * @param bool $create Force database creation
     */
    public function __construct($dbFileName, $create = false)
    {
        $this->dbFileName = $dbFileName;
        if ($create) {
            $this->createDatabase($dbFileName);
        } else {
            $this->openDatabase($dbFileName);
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
        if (!is_writable($dbFileName)) {
            $this->readOnly = true;
        }
        try {
            // Init the Data Source Name
            $dsn = 'sqlite:' . $dbFileName;
            // Open the database
            $this->db = new PDO($dsn); // Send an exception if error
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec('pragma synchronous = off');
            $this->addSqliteFunctions();
        } catch (Exception $e) {
            $error = sprintf('Cannot open database [%s]: %s', $dsn, $e->getMessage());
            throw new Exception($error);
        }
    }

    /**
     * Summary of addSqliteFunctions
     * @suppress PHP0418 https://docs.devsense.com/en/vs/code%20validation/configuration#suppress-phpdoc-tag
     * @return void
     */
    protected function addSqliteFunctions()
    {
        // Add title_sort() function for books and series
        $this->db->sqliteCreateFunction('title_sort', function ($s) {
            return BaseInfo::getTitleSort($s);
        }, 1);
        // Check if we need to add unixepoch() for notes_db.notes
        $sql = 'SELECT sqlite_version() as version;';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        if ($post = $stmt->fetchObject()) {
            if ($post->version >= '3.38') {
                return;
            }
        }
        // @todo no support for actual datetime conversion here
        // mtime REAL DEFAULT (unixepoch('subsec')),
        $this->db->sqliteCreateFunction('unixepoch', function ($s) {
            if (!empty($s) && $s == 'subsec') {
                return microtime(true);
            }
            return time();
        }, 1);
    }

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
        $sqlFileName = dirname(__DIR__) . '/' . static::$createDbSql;
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
        $this->createDbTables($content, true);
    }

    /**
     * Attach an sqlite database to existing db connection
     *
     * @param string $dbFileName Database file name
     * @param string $attachDatabase
     * @param bool $create Force database creation
     *
     * @throws Exception if error
     *
     * @return void
     */
    protected function attachDatabase($dbFileName, $attachDatabase, $create = false)
    {
        // Remove the database file
        if ($create && file_exists($dbFileName) && !unlink($dbFileName)) {
            $error = sprintf('Cannot remove database file: %s', $dbFileName);
            throw new Exception($error);
        }

        // Attach the database file
        try {
            $sql = "ATTACH DATABASE '{$dbFileName}' AS {$attachDatabase};";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } catch (Exception $e) {
            $error = sprintf('Cannot attach %s database [%s]: %s', $attachDatabase, $dbFileName, $e->getMessage());
            throw new Exception($error);
        }
    }

    /**
     * Get list of databases (open or attach)
     *
     * @return array<mixed>
     */
    public function getDatabaseList()
    {
        // PRAGMA database_list;
        $sql = 'select * from pragma_database_list;';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $databases = [];
        while ($post = $stmt->fetchObject()) {
            $databases[$post->name] = (array) $post;
        }
        return $databases;
    }

    /**
     * Summary of getDbConnection
     * @return PDO|null
     */
    public function getDbConnection()
    {
        return $this->db;
    }

    /**
     * Create database tables, indexes, triggers etc.
     *
     * @param string $sqlString
     * @param bool $extraFields
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function createDbTables($sqlString, $extraFields = false)
    {
        // Create the database tables
        try {
            $sqlArray = explode('CREATE ', $sqlString);
            foreach ($sqlArray as $sql) {
                $sql = trim($sql);
                if (empty($sql)) {
                    continue;
                }
                $sql = 'CREATE ' . $sql;
                $str = strtolower($sql);
                // Skip sqlite views
                if (str_contains($str, 'create view')) {
                    continue;
                }
                // Skip triggers using custom functions
                if (str_contains($str, 'uuid4')) {
                    continue;
                }
                // Skip full-text search tables and triggers
                if (str_contains($str, 'fts')) {
                    continue;
                }
                if ($extraFields) {
                    // Add 'calibre_database_field_cover' field for books
                    if (str_contains($sql, 'has_cover BOOL DEFAULT 0,')) {
                        $sql = str_replace('has_cover BOOL DEFAULT 0,', 'has_cover BOOL DEFAULT 0,' . ' cover TEXT NOT NULL DEFAULT "",', $sql);
                    }
                    // Add 'calibre_database_field_sort' field for tags
                    if (str_contains($sql, 'CREATE TABLE tags ')) {
                        $sql = str_replace('name TEXT NOT NULL COLLATE NOCASE,', 'name TEXT NOT NULL COLLATE NOCASE,' . ' sort TEXT COLLATE NOCASE,', $sql);
                    }
                }
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            }
        } catch (Exception $e) {
            $error = sprintf('Cannot create database: %s', $e->getMessage());
            throw new Exception($error);
        }
    }
}
