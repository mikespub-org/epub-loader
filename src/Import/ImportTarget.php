<?php
/**
 * ImportTarget class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\DatabaseLoader;
use Marsender\EPubLoader\Models\BookInfo;
use Exception;

abstract class ImportTarget extends DatabaseLoader
{
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
        $this->dbFileName = $dbFileName;
        if ($create) {
            $this->createDatabases($dbFileName);
            if (!empty($bookIdsFileName)) {
                $this->loadBookIds($bookIdsFileName);
            }
        } else {
            $this->openDatabase($dbFileName);
            $notesFileName = dirname($dbFileName) . '/.calnotes/notes.db';
            if (file_exists($notesFileName)) {
                $this->attachDatabase($notesFileName, 'notes_db');
            }
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
     * Create sqlite databases (Calibre metadata + notes)
     *
     * @param string $dbFileName Database file name
     * @throws Exception if error
     * @return void
     */
    protected function createDatabases($dbFileName)
    {
        // Create metadata database
        $this->createDatabase($dbFileName);

        // Attach notes database
        $notesFileName = dirname($dbFileName) . '/.calnotes/notes.db';
        $notesDbPath = dirname($notesFileName);
        if (!is_dir($notesDbPath)) {
            if (!mkdir($notesDbPath, 0o755, true)) {
                throw new Exception('Cannot create directory: ' . $notesDbPath);
            }
        }
        $this->attachDatabase($notesFileName, 'notes_db', true);

        // Read notes sql file
        $sqlFileName = dirname(__DIR__, 2) . '/' . static::$notesDbSql;
        $content = file_get_contents($sqlFileName);
        if ($content === false) {
            $error = sprintf('Cannot read sql file: %s', $sqlFileName);
            throw new Exception($error);
        }

        // Create notes database tables
        $this->createDbTables($content, false);
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
