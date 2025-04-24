<?php

/**
 * DatabaseWriter class
 */

namespace Marsender\EPubLoader\Workflows\Writers;

use Marsender\EPubLoader\DatabaseLoader;
use Marsender\EPubLoader\Models\BookInfo;
use PDO;
use Exception;

abstract class DatabaseWriter extends TargetWriter
{
    /** @var DatabaseLoader */
    protected $dbLoader;
    protected string $dbFileName;
    /** @var PDO|null */
    protected $db = null;

    /**
     * Open a Calibre database (or create if database does not exist)
     *
     * @param string $dbFileName Calibre database file name
     * @param boolean $create Force database creation
     */
    public function __construct($dbFileName, $create = false)
    {
        $this->dbFileName = $dbFileName;
        if ($create) {
            $this->createDatabases($dbFileName);
        } else {
            $this->dbLoader = new DatabaseLoader($dbFileName, false);
            if ($this->dbLoader->readOnly) {
                throw new Exception('Invalid database as target writer (read-only)');
            }
            $notesFileName = dirname($dbFileName) . '/.calnotes/notes.db';
            if (file_exists($notesFileName)) {
                $this->dbLoader->attachDatabase($notesFileName, 'notes_db');
            }
        }
        $this->db = $this->dbLoader->getDbConnection();
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
    abstract public function addBook($bookInfo, $bookId = 0, $sortField = 'sort');

    /**
     * Create sqlite databases (Calibre metadata + notes)
     *
     * @param string $dbFileName Database file name
     * @throws Exception if error
     * @return void
     */
    protected function createDatabases($dbFileName)
    {
        // Create metadata database
        $this->dbLoader = new DatabaseLoader($dbFileName, true);

        // Test import of existing databases
        $dbPrefix = str_replace('metadata.db', '', basename($dbFileName));

        // Attach notes database
        $notesFileName = dirname($dbFileName) . '/.calnotes/' . $dbPrefix . 'notes.db';
        $notesDbPath = dirname($notesFileName);
        if (!is_dir($notesDbPath)) {
            if (!mkdir($notesDbPath, 0o755, true)) {
                throw new Exception('Cannot create directory: ' . $notesDbPath);
            }
        }
        $this->dbLoader->attachDatabase($notesFileName, 'notes_db', true);

        // Read notes sql file
        $sqlFileName = dirname(__DIR__, 3) . '/' . DatabaseLoader::NOTES_DB_SQL;
        $content = file_get_contents($sqlFileName);
        if ($content === false) {
            $error = sprintf('Cannot read sql file: %s', $sqlFileName);
            throw new Exception($error);
        }

        // Create notes database tables
        $this->dbLoader->createDbTables($content, false);
    }
}
