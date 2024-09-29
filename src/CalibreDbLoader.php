<?php
/**
 * CalibreDbLoader class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

use Exception;
use PDO;

/**
 * CalibreDbLoader class allows to open or create a new Calibre database,
 * and then add BookInfos objects into the database
 */
class CalibreDbLoader
{
    /** @var PDO|null */
    protected $mDb = null;
    /** @var string|null */
    protected $mDbFileName = null;
    /** @var PDO|null */
    protected $notesDb;

    /**
     * Open a Calibre database
     *
     * @param string $inDbFileName Calibre database file name
     */
    public function __construct($inDbFileName)
    {
        $this->mDbFileName = $inDbFileName;
        $this->openDatabase($inDbFileName);
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
     * Check database for debug
     *
     * @return void
     */
    protected function checkDatabase()
    {
        // Retrieve some infos for check only
        $sql = 'select id, title, sort from books';
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute();
        while ($post = $stmt->fetchObject()) {
            $id = $post->id;
            $title = $post->title;
            $sort = $post->sort;
        }
    }

    /**
     * Summary of setAuthorLink
     * @param int $authorId
     * @param string $link
     * @return bool
     */
    public function setAuthorLink($authorId, $link)
    {
        $sql = 'update authors set link = ? where id = ?';
        $stmt = $this->mDb->prepare($sql);
        return $stmt->execute([$link, $authorId]);
    }

    /**
     * Summary of getAuthors
     * @param int|null $authorId
     * @return array<mixed>
     */
    public function getAuthors($authorId = null)
    {
        $sql = 'select id, name, sort, link from authors';
        $params = [];
        if (!empty($authorId)) {
            $sql .= ' where id = ?';
            $params[] = $authorId;
        }
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute($params);
        $authors = [];
        while ($post = $stmt->fetchObject()) {
            $authors[$post->id] = (array) $post;
        }
        return $authors;
    }

    /**
     * Summary of getBooks
     * @param int|null $bookId
     * @param int|null $authorId
     * @return array<mixed>
     */
    public function getBooks($bookId = null, $authorId = null)
    {
        $sql = 'select books.id as id, books.title as title, author from books, books_authors_link
        where book = books.id';
        $params = [];
        if (!empty($bookId)) {
            $sql .= ' and book = ?';
            $params[] = $bookId;
        }
        if (!empty($authorId)) {
            $sql .= ' and author = ?';
            $params[] = $authorId;
        }
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute($params);
        $books = [];
        $bookIdList = [];
        while ($post = $stmt->fetchObject()) {
            $books[$post->id] = (array) $post;
            $books[$post->id]['identifiers'] = [];
            $bookIdList[] = $post->id;
        }
        $sql = 'select id, book, type, val as value from identifiers
        where book IN (' . str_repeat('?,', count($bookIdList) - 1) . '?)';
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute($bookIdList);
        while ($post = $stmt->fetchObject()) {
            $books[$post->book]['identifiers'][$post->id] = (array) $post;
            $url = $this->getIdentifierUrl($post->type, $post->value);
            if (!empty($url)) {
                $books[$post->book]['identifiers'][$post->id]['url'] = $url;
            }
        }
        return $books;
    }

    /**
     * Summary of getBooksByAuthor
     * @param int $authorId
     * @return array<mixed>
     */
    public function getBooksByAuthor($authorId)
    {
        return $this->getBooks(null, $authorId);
    }

    /**
     * Summary of getBookCount
     * @return array<mixed>
     */
    public function getBookCount()
    {
        $sql = 'select author, count(*) as numitems from books_authors_link group by author';
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute();
        $count = [];
        while ($post = $stmt->fetchObject()) {
            $count[$post->author] = $post->numitems;
        }
        return $count;
    }

    /**
     * Summary of getIdentifierUrl
     * @param string $type
     * @param mixed $value
     * @return string
     */
    public function getIdentifierUrl($type, $value)
    {
        if (empty($value)) {
            return '';
        }
        $url = match ($type) {
            'google' => Metadata\Sources\GoogleBooksMatch::link($value),
            'wd' => Metadata\Sources\WikiDataMatch::link($value),
            'olid' => Metadata\Sources\OpenLibraryMatch::link($value),
            default => '',
        };
        return $url;
    }

    /**
     * Summary of updateIdentifier
     * @param int $id
     * @param mixed $value
     * @return bool
     */
    public function updateIdentifier($id, $value)
    {
        $sql = 'update identifiers set val = ? where id = ?';
        $stmt = $this->mDb->prepare($sql);
        return $stmt->execute([$value, $id]);
    }

    /**
     * Summary of insertIdentifier
     * @param int $bookId
     * @param string $type
     * @param mixed $value
     * @return bool
     */
    public function insertIdentifier($bookId, $type, $value)
    {
        $sql = 'insert into identifiers(book, type, val) values(?, ?, ?)';
        $stmt = $this->mDb->prepare($sql);
        return $stmt->execute([$bookId, $type, $value]);
    }

    /**
     * Summary of getSeries
     * @param int|null $seriesId
     * @param int|null $authorId
     * @param int|null $bookId
     * @return array<mixed>
     */
    public function getSeries($seriesId = null, $authorId = null, $bookId = null)
    {
        $sql = 'select series.id as id, series.name as name, author from series, books_series_link, books, books_authors_link
        where books_series_link.series = series.id and books_series_link.book = books.id and books_authors_link.book = books.id';
        $params = [];
        if (!empty($seriesId)) {
            $sql .= ' and series.id = ?';
            $params[] = $seriesId;
        }
        if (!empty($authorId)) {
            $sql .= ' and author = ?';
            $params[] = $authorId;
        }
        if (!empty($bookId)) {
            $sql .= ' and books.id = ?';
            $params[] = $bookId;
        }
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute($params);
        $series = [];
        while ($post = $stmt->fetchObject()) {
            $series[$post->id] = (array) $post;
        }
        return $series;
    }

    /**
     * Summary of getSeriesByAuthor
     * @param int $authorId
     * @return array<mixed>
     */
    public function getSeriesByAuthor($authorId)
    {
        return $this->getSeries(null, $authorId);
    }

    /**
     * Summary of getSeriesByBook
     * @param int $bookId
     * @return array<mixed>
     */
    public function getSeriesByBook($bookId)
    {
        return $this->getSeries(null, null, $bookId);
    }

    /**
     * Summary of getSeriesCount
     * @return array<mixed>
     */
    public function getSeriesCount()
    {
        $sql = 'select author, count(distinct series) as numitems from books_series_link, books, books_authors_link
        where books_series_link.book = books.id and books_authors_link.book = books.id
        group by author';
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute();
        $count = [];
        while ($post = $stmt->fetchObject()) {
            $count[$post->author] = $post->numitems;
        }
        return $count;
    }

    /**
     * Summary of hasNotes
     * @return bool
     */
    public function hasNotes()
    {
        if (file_exists(dirname((string) $this->mDbFileName) . '/.calnotes/notes.db')) {
            return true;
        }
        return false;
    }

    /**
     * Summary of getNotesDb
     * @return PDO|null
     */
    public function getNotesDb()
    {
        if (!$this->hasNotes()) {
            return null;
        }
        $notesFileName = dirname((string) $this->mDbFileName) . '/.calnotes/notes.db';
        try {
            // Init the Data Source Name
            $dsn = 'sqlite:' . $notesFileName;
            // Open the database
            $this->notesDb = new PDO($dsn); // Send an exception if error
            $this->notesDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->notesDb->exec('pragma synchronous = off');
            return $this->notesDb;
        } catch (Exception $e) {
            $error = sprintf('Cannot open database [%s]: %s', $dsn, $e->getMessage());
            throw new Exception($error);
        }
    }

    /**
     * Summary of getNotes
     * @param string $colName
     * @param array<mixed> $itemIdList
     * @return array<mixed>
     */
    public function getNotes($colName, $itemIdList = [])
    {
        if (is_null($this->getNotesDb())) {
            return [];
        }
        $sql = 'select item, colname, doc, mtime from notes';
        $params = [];
        $sql .= ' where colname = ?';
        $params[] = $colName;
        if (!empty($itemIdList)) {
            $sql .= ' and item in (' . str_repeat('?,', count($itemIdList) - 1) . '?)';
            $params = array_merge($params, $itemIdList);
        }
        $stmt = $this->notesDb->prepare($sql);
        $stmt->execute($params);
        $notes = [];
        while ($post = $stmt->fetchObject()) {
            $notes[$post->item] = (array) $post;
        }
        return $notes;
    }

    /**
     * Summary of getNotesCount
     * @return array<mixed>
     */
    public function getNotesCount()
    {
        if (is_null($this->getNotesDb())) {
            return [];
        }
        $sql = 'select colname, count(*) as numitems from notes group by colname';
        $stmt = $this->notesDb->prepare($sql);
        $stmt->execute();
        $count = [];
        while ($post = $stmt->fetchObject()) {
            $count[$post->colname] = $post->numitems;
        }
        return $count;
    }

    /**
     * Summary of getResourcePath
     * @param string $hash
     * @return string|null
     */
    public function getResourcePath($hash)
    {
        if (!$this->hasNotes()) {
            return null;
        }
        $resourceDir = dirname((string) $this->mDbFileName) . '/.calnotes/resources';
        if (!is_dir($resourceDir)) {
            return null;
        }
        [$alg, $digest] = explode('-', $hash);
        $path = $resourceDir . '/' . substr($digest, 0, 2) . '/' . $hash;
        if (!is_file($path)) {
            return null;
        }
        return $path;
    }
}
