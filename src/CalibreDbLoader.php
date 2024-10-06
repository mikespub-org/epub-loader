<?php
/**
 * CalibreDbLoader class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

use Marsender\EPubLoader\Metadata\Sources\BaseMatch;
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
    public int $limit = 500;
    public bool $readOnly = false;

    /**
     * Open a Calibre database
     *
     * @param string $inDbFileName Calibre database file name
     */
    public function __construct($inDbFileName)
    {
        $this->mDbFileName = $inDbFileName;
        $this->openDatabase($inDbFileName);
        if (!is_writable($this->mDbFileName)) {
            $this->readOnly = true;
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
     * Summary of getStats
     * @see https://www.sqlite.org/lang_analyze.html
     * @return array<string, int>
     */
    public function getStats()
    {
        /**
        // this throws an error for read-only databases
        $sql = "analyze; select tbl, idx, stat from sqlite_stat1 where tbl in ('authors', 'books', 'series')";
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute();
        $stats = [];
        while ($post = $stmt->fetchObject()) {
            $stats[$post->tbl] ??= (int) explode(' ', $post->stat)[0];
        }
        return $stats;
         */
        $stats = [
            'authors' => 0,
            'books' => 0,
            'series' => 0,
        ];
        $tables = ['authors', 'books', 'series'];
        foreach ($tables as $table) {
            $sql = 'select count(*) as count from ' . $table;
            $stmt = $this->mDb->prepare($sql);
            $stmt->execute();
            if ($post = $stmt->fetchObject()) {
                $stats[$table] = $post->count;
            }
        }
        return $stats;
    }

    /**
     * Summary of getCountPaging
     * @param int|null $count
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>|null
     */
    public function getCountPaging($count = null, $sort = null, $offset = null)
    {
        if (empty($count) || $count <= $this->limit) {
            return null;
        }
        $offset ??= 0;
        $prefix = '';
        if (!empty($sort) && $sort != 'id') {
            $prefix = 'sort=' . $sort . '&';
        }
        $paging = [
            'first' => '',
            'prev' => '',
            'next' => '',
            'last' => '',
        ];
        if (!empty($offset)) {
            $paging['first'] = $prefix . 'offset=0';
            $paging['prev'] = $prefix . 'offset=' . (string) ($offset - $this->limit);
        }
        $max = $this->limit * intdiv($count - 1, $this->limit);
        if ($offset < $max) {
            $paging['next'] = $prefix . 'offset=' . (string) ($offset + $this->limit);
            $paging['last'] = $prefix . 'offset=' . (string) $max;
        }
        return $paging;
    }

    /**
     * Summary of getAuthors
     * @param int|null $authorId
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>
     */
    public function getAuthors($authorId = null, $sort = null, $offset = null)
    {
        $sql = 'select id, name, sort, link from authors';
        $params = [];
        if (!empty($authorId)) {
            $sql .= ' where id = ?';
            $params[] = $authorId;
        }
        if (!empty($sort) && in_array($sort, ['id', 'name', 'sort'])) {
            $sql .= ' order by ' . $sort;
        } else {
            $sql .= ' order by id';
        }
        // we will order & slice later for books or series - see ActionHandler::addAuthorInfo()
        if (empty($sort) || !in_array($sort, ['books', 'series'])) {
            $sql .= ' limit ' . $this->limit;
            if (!empty($offset) && is_int($offset)) {
                $sql .= ' offset ' . (string) $offset;
            }
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
     * Summary of getAuthorNames
     * @return array<mixed>
     */
    public function getAuthorNames()
    {
        // no limit for author names!?
        $sql = 'select id, name from authors';
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute();
        $authors = [];
        while ($post = $stmt->fetchObject()) {
            $authors[$post->id] = $post->name;
        }
        return $authors;
    }

    /**
     * Summary of getAuthorCount
     * @return int|null
     */
    public function getAuthorCount()
    {
        $sql = 'select count(*) as count from authors';
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute();
        if ($post = $stmt->fetchObject()) {
            return $post->count;
        }
        return null;
    }

    /**
     * Summary of getAuthorPaging
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>|null
     */
    public function getAuthorPaging($sort = null, $offset = null)
    {
        $count = $this->getAuthorCount();
        return $this->getCountPaging($count, $sort, $offset);
    }

    /**
     * Summary of setAuthorLink
     * @param int $authorId
     * @param string $link
     * @return bool
     */
    public function setAuthorLink($authorId, $link)
    {
        if ($this->readOnly) {
            return false;
        }
        $sql = 'update authors set link = ? where id = ?';
        $stmt = $this->mDb->prepare($sql);
        return $stmt->execute([$link, $authorId]);
    }

    /**
     * Summary of getBooks
     * @param int|null $bookId
     * @param int|null $authorId
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>
     */
    public function getBooks($bookId = null, $authorId = null, $sort = null, $offset = null)
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
        if (!empty($sort) && in_array($sort, ['id', 'title'])) {
            $sql .= ' order by ' . $sort;
        } else {
            $sql .= ' order by id';
        }
        $sql .= ' limit ' . $this->limit;
        if (!empty($offset) && is_int($offset)) {
            $sql .= ' offset ' . (string) $offset;
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
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>
     */
    public function getBooksByAuthor($authorId, $sort = null, $offset = null)
    {
        return $this->getBooks(null, $authorId, $sort, $offset);
    }

    /**
     * Summary of getBookCount
     * @param int|null $authorId
     * @return array<mixed>
     */
    public function getBookCount($authorId = null)
    {
        $sql = 'select author, count(*) as numitems from books_authors_link';
        $params = [];
        if (!empty($authorId)) {
            $sql .= ' where author = ?';
            $params[] = $authorId;
        }
        $sql .= ' group by author';
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute($params);
        $count = [];
        while ($post = $stmt->fetchObject()) {
            $count[$post->author] = $post->numitems;
        }
        return $count;
    }

    /**
     * Summary of getBookPaging
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>|null
     */
    public function getBookPaging($sort = null, $offset = null)
    {
        // get the total of all books per author
        $count = $this->getBookCount();
        $total = array_sum(array_values($count));
        return $this->getCountPaging($total, $sort, $offset);
    }

    /**
     * Summary of getIdentifierUrl
     * @param string $type
     * @param mixed $value
     * @return string
     */
    public function getIdentifierUrl($type, $value)
    {
        return BaseMatch::getTypeLink($type, $value);
    }

    /**
     * Summary of updateIdentifier
     * @param int $id
     * @param mixed $value
     * @return bool
     */
    public function updateIdentifier($id, $value)
    {
        if ($this->readOnly) {
            return false;
        }
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
        if ($this->readOnly) {
            return false;
        }
        $sql = 'insert into identifiers(book, type, val) values(?, ?, ?)';
        $stmt = $this->mDb->prepare($sql);
        return $stmt->execute([$bookId, $type, $value]);
    }

    /**
     * Summary of getSeries
     * @param int|null $seriesId
     * @param int|null $authorId
     * @param int|null $bookId
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>
     */
    public function getSeries($seriesId = null, $authorId = null, $bookId = null, $sort = null, $offset = null)
    {
        $sql = 'select distinct series.id as id, series.name as name, series.link as link, author from series, books_series_link, books, books_authors_link
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
        if (!empty($sort) && in_array($sort, ['id', 'name', 'author'])) {
            $sql .= ' order by ' . $sort;
        } else {
            $sql .= ' order by id';
        }
        $sql .= ' limit ' . $this->limit;
        if (!empty($offset) && is_int($offset)) {
            $sql .= ' offset ' . (string) $offset;
        }
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute($params);
        $series = [];
        // series can have multiple authors
        while ($post = $stmt->fetchObject()) {
            $series[(string) $post->id . '.' . (string) $post->author] = (array) $post;
        }
        return $series;
    }

    /**
     * Summary of getSeriesByAuthor
     * @param int $authorId
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>
     */
    public function getSeriesByAuthor($authorId, $sort = null, $offset = null)
    {
        return $this->getSeries(null, $authorId, null, $sort, $offset);
    }

    /**
     * Summary of getSeriesByBook
     * @param int $bookId
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>
     */
    public function getSeriesByBook($bookId, $sort = null, $offset = null)
    {
        return $this->getSeries(null, null, $bookId, $sort, $offset);
    }

    /**
     * Summary of getSeriesCount
     * @param int|null $authorId
     * @return array<mixed>
     */
    public function getSeriesCount($authorId = null)
    {
        $sql = 'select author, count(distinct series) as numitems from books_series_link, books, books_authors_link
        where books_series_link.book = books.id and books_authors_link.book = books.id';
        $params = [];
        if (!empty($authorId)) {
            $sql .= ' and author = ?';
            $params[] = $authorId;
        }
        $sql .= ' group by author';
        $stmt = $this->mDb->prepare($sql);
        $stmt->execute($params);
        $count = [];
        while ($post = $stmt->fetchObject()) {
            $count[$post->author] = $post->numitems;
        }
        return $count;
    }

    /**
     * Summary of getSeriesPaging
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>|null
     */
    public function getSeriesPaging($sort = null, $offset = null)
    {
        // get the total of all series per author
        $count = $this->getSeriesCount();
        $total = array_sum(array_values($count));
        return $this->getCountPaging($total, $sort, $offset);
    }

    /**
     * Summary of setSeriesLink
     * @param int $seriesId
     * @param string $link
     * @return bool
     */
    public function setSeriesLink($seriesId, $link)
    {
        if ($this->readOnly) {
            return false;
        }
        $sql = 'update series set link = ? where id = ?';
        $stmt = $this->mDb->prepare($sql);
        return $stmt->execute([$link, $seriesId]);
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
