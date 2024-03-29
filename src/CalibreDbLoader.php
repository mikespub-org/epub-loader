<?php
/**
 * CalibreDbLoader class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

use Marsender\EPubLoader\Metadata\BookInfos;
use Exception;
use PDO;

define('PDO_SUCCES_CODE', '00000');

/**
 * Calibre database sql file that comes unmodified from Calibre project:
 * https://raw.githubusercontent.com/kovidgoyal/calibre/master/resources/metadata_sqlite.sql
 */
define('CalibreCreateDbSql', dirname(__DIR__) . '/schema/metadata_sqlite.sql');

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
    /** @var array<string, mixed>|null */
    protected $mBookId = null;
    /** @var PDO|null */
    protected $notesDb;

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
        $this->mDbFileName = $inDbFileName;
        if ($inCreate) {
            $this->CreateDatabase($inDbFileName);
            if (!empty($inBookIdsFileName)) {
                $this->LoadBookIds($inBookIdsFileName);
            }
        } else {
            $this->OpenDatabase($inDbFileName);
        }
    }

    /**
     * Descructor
     */
    public function __destruct()
    {
        $this->SaveBookIds();
    }

    /**
     * Load the book ids map in order to reuse calibe book id when recreating database
     *
     * @param string $inBookIdsFileName File name containing a map of file names to calibre book ids
     *
     * @return void
     */
    protected function LoadBookIds($inBookIdsFileName)
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
    protected function SaveBookIds()
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
     * Summary of GetBookId
     * @param mixed $inBookFileName
     * @return int
     */
    protected function GetBookId($inBookFileName)
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

    /**
     * Create an sqlite database
     *
     * @param string $inDbFileName Database file name
     *
     * @throws Exception if error
     *
     * @return void
     */
    protected function CreateDatabase($inDbFileName)
    {
        // Read the sql file
        $content = file_get_contents(CalibreCreateDbSql);
        if ($content === false) {
            $error = sprintf('Cannot read sql file: %s', CalibreCreateDbSql);
            throw new Exception($error);
        }

        // Remove the database file
        if (file_exists($inDbFileName) && !unlink($inDbFileName)) {
            $error = sprintf('Cannot remove database file: %s', $inDbFileName);
            throw new Exception($error);
        }

        // Create the new database file
        $this->OpenDatabase($inDbFileName);

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
                if (strpos($str, 'create view') !== false) {
                    continue;
                }
                if (strpos($str, 'title_sort') !== false) {
                    continue;
                }
                // Add 'calibre_database_field_cover' field
                if (strpos($sql, 'has_cover BOOL DEFAULT 0,') !== false) {
                    $sql = str_replace('has_cover BOOL DEFAULT 0,', 'has_cover BOOL DEFAULT 0,' . ' cover TEXT NOT NULL DEFAULT "",', $sql);
                }
                // Add 'calibre_database_field_sort' field
                if (strpos($sql, 'CREATE TABLE tags ') !== false) {
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
    protected function OpenDatabase($inDbFileName)
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
     * Add an epub to the db
     *
     * @param string $inBasePath Epub base directory
     * @param string $inFileName Epub file name (from base directory)
     *
     * @throws Exception if error
     *
     * @return string Empty string or error if any
     */
    public function AddEpub($inBasePath, $inFileName)
    {
        $error = '';

        try {
            // Load the book infos
            $bookInfos = new BookInfos();
            $bookInfos->LoadFromEpub($inBasePath, $inFileName);
            // Add the book
            $bookId = $this->GetBookId($inFileName);
            $this->AddBook($bookInfos, $bookId);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return $error;
    }

    /**
     * Add a new book into the db
     *
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $inBookId Book id in the calibre db (or 0 for auto incrementation)
     * @param string $sortField Add 'calibre_database_field_sort' field
     *
     * @throws Exception if error
     *
     * @return void
     */
    protected function AddBook($inBookInfo, $inBookId, $sortField = 'sort')
    {
        $errors = [];

        // Check if the book uuid does not already exist
        $sql = 'select b.id, b.title, b.path, d.name, d.format from books as b, data as d where d.book = b.id and uuid=:uuid';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':uuid', $inBookInfo->mUuid);
        $stmt->execute();
        while ($post = $stmt->fetchObject()) {
            $error = sprintf('Warning: Multiple book id for uuid: %s (already in file "%s/%s.%s" title "%s")', $inBookInfo->mUuid, $post->path, $post->name, $inBookInfo->mFormat, $post->title);
            $errors[] = $error;
            // Set a new uuid
            $inBookInfo->CreateUuid();
            break;
        }
        // Add the book
        $sql = 'insert into books(';
        if ($inBookId) {
            $sql .= 'id, ';
        }
        $sql .= 'title, sort, timestamp, pubdate, last_modified, series_index, uuid, path, has_cover, cover, isbn) values(';
        if ($inBookId) {
            $sql .= ':id, ';
        }
        $sql .= ':title, :sort, :timestamp, :pubdate, :lastmodified, :serieindex, :uuid, :path, :hascover, :cover, :isbn)';
        $timeStamp = BookInfos::GetSqlDate($inBookInfo->mTimeStamp);
        $pubDate = BookInfos::GetSqlDate(empty($inBookInfo->mCreationDate) ? '2000-01-01 00:00:00' : $inBookInfo->mCreationDate);
        $lastModified = BookInfos::GetSqlDate(empty($inBookInfo->mModificationDate) ? '2000-01-01 00:00:00' : $inBookInfo->mModificationDate);
        $hasCover = empty($inBookInfo->mCover) ? 0 : 1;
        if (empty($inBookInfo->mCover)) {
            $error = 'Warning: Cover not found';
            $errors[] = $error;
        }
        $cover = str_replace('OEBPS/', $inBookInfo->mName . '/', $inBookInfo->mCover);
        $stmt = $this->mDb->prepare($sql);
        if ($inBookId) {
            $stmt->bindParam(':id', $inBookId);
        }
        $stmt->bindParam(':title', $inBookInfo->mTitle);
        $sortString = BookInfos::GetSortString($inBookInfo->mTitle);
        $stmt->bindParam(':sort', $sortString);
        $stmt->bindParam(':timestamp', $timeStamp);
        $stmt->bindParam(':pubdate', $pubDate);
        $stmt->bindParam(':lastmodified', $lastModified);
        $stmt->bindParam(':serieindex', $inBookInfo->mSerieIndex);
        $stmt->bindParam(':uuid', $inBookInfo->mUuid);
        $stmt->bindParam(':path', $inBookInfo->mPath);
        $stmt->bindParam(':hascover', $hasCover, PDO::PARAM_INT);
        $stmt->bindParam(':cover', $cover);
        $stmt->bindParam(':isbn', $inBookInfo->mIsbn);
        $stmt->execute();
        // Get the book id
        $sql = 'select id from books where uuid=:uuid';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':uuid', $inBookInfo->mUuid);
        $stmt->execute();
        $idBook = null;
        $post = $stmt->fetchObject();
        if ($post) {
            $idBook = $post->id;
        }
        if (empty($idBook)) {
            $error = sprintf('Cannot find book id for uuid: %s', $inBookInfo->mUuid);
            throw new Exception($error);
        }
        if ($inBookId && $idBook != $inBookId) {
            $error = sprintf('Incorrect book id=%d vs %d for uuid: %s', $idBook, $inBookId, $inBookInfo->mUuid);
            throw new Exception($error);
        }
        // Add the book data (formats)
        $formats = [
            $inBookInfo->mFormat,
            'pdf',
        ];
        foreach ($formats as $format) {
            $fileName = sprintf('%s%s%s%s%s.%s', $inBookInfo->mBasePath, DIRECTORY_SEPARATOR, $inBookInfo->mPath, DIRECTORY_SEPARATOR, $inBookInfo->mName, $format);
            if (!is_readable($fileName)) {
                if ($format == $inBookInfo->mFormat) {
                    $error = sprintf('Cannot read file: %s', $fileName);
                    throw new Exception($error);
                }
                continue;
            }
            $uncompressedSize = filesize($fileName);
            $sql = 'insert into data(book, format, name, uncompressed_size) values(:idBook, :format, :name, :uncompressedSize)';
            $stmt = $this->mDb->prepare($sql);
            $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
            $format = strtoupper($format);
            $stmt->bindParam(':format', $format); // Calibre format is uppercase
            $stmt->bindParam(':name', $inBookInfo->mName);
            $stmt->bindParam(':uncompressedSize', $uncompressedSize);
            $stmt->execute();
        }
        // Add the book comments
        $sql = 'insert into comments(book, text) values(:idBook, :text)';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
        $stmt->bindParam(':text', $inBookInfo->mDescription);
        $stmt->execute();
        // Add the book identifiers
        if (!empty($inBookInfo->mUri)) {
            $sql = 'insert into identifiers(book, type, val) values(:idBook, :type, :value)';
            $identifiers = [];
            $identifiers['URI'] = $inBookInfo->mUri;
            $identifiers['ISBN'] = $inBookInfo->mIsbn;
            foreach ($identifiers as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                $stmt = $this->mDb->prepare($sql);
                $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
                $stmt->bindParam(':type', $key);
                $stmt->bindParam(':value', $value);
                $stmt->execute();
            }
        }
        // Add the book serie
        if (!empty($inBookInfo->mSerie)) {
            // Get the serie id
            $sql = 'select id from series where name=:serie';
            $stmt = $this->mDb->prepare($sql);
            $stmt->bindParam(':serie', $inBookInfo->mSerie);
            $stmt->execute();
            $post = $stmt->fetchObject();
            if ($post) {
                $idSerie = $post->id;
            } else {
                // Add a new serie
                $sql = 'insert into series(name, sort) values(:serie, :sort)';
                $stmt = $this->mDb->prepare($sql);
                $stmt->bindParam(':serie', $inBookInfo->mSerie);
                $sortString = BookInfos::GetSortString($inBookInfo->mSerie);
                $stmt->bindParam(':sort', $sortString);
                $stmt->execute();
                // Get the serie id
                $sql = 'select id from series where name=:serie';
                $stmt = $this->mDb->prepare($sql);
                $stmt->bindParam(':serie', $inBookInfo->mSerie);
                $stmt->execute();
                $idSerie = null;
                while ($post = $stmt->fetchObject()) {
                    if (!isset($idSerie)) {
                        $idSerie = $post->id;
                    } else {
                        $error = sprintf('Multiple series for name: %s', $inBookInfo->mSerie);
                        throw new Exception($error);
                    }
                }
                if (!isset($idSerie)) {
                    $error = sprintf('Cannot find serie id for name: %s', $inBookInfo->mSerie);
                    throw new Exception($error);
                }
            }
            // Add the book serie link
            $sql = 'insert into books_series_link(book, series) values(:idBook, :idSerie)';
            $stmt = $this->mDb->prepare($sql);
            $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
            $stmt->bindParam(':idSerie', $idSerie, PDO::PARAM_INT);
            $stmt->execute();
        }
        // Add the book authors
        foreach ($inBookInfo->mAuthors as $authorSort => $author) {
            // Get the author id
            $sql = 'select id from authors where name=:author';
            $stmt = $this->mDb->prepare($sql);
            $stmt->bindParam(':author', $author);
            $stmt->execute();
            $post = $stmt->fetchObject();
            if ($post) {
                $idAuthor = $post->id;
            } else {
                // Add a new author
                $sql = 'insert into authors(name, sort) values(:author, :sort)';
                $stmt = $this->mDb->prepare($sql);
                $stmt->bindParam(':author', $author);
                $sortString = BookInfos::GetSortString($authorSort);
                $stmt->bindParam(':sort', $sortString);
                $stmt->execute();
                // Get the author id
                $sql = 'select id from authors where name=:author';
                $stmt = $this->mDb->prepare($sql);
                $stmt->bindParam(':author', $author);
                $stmt->execute();
                $idAuthor = null;
                while ($post = $stmt->fetchObject()) {
                    if (!isset($idAuthor)) {
                        $idAuthor = $post->id;
                    } else {
                        $error = sprintf('Multiple authors for name: %s', $author);
                        throw new Exception($error);
                    }
                }
                if (!isset($idAuthor)) {
                    $error = sprintf('Cannot find author id for name: %s', $author);
                    throw new Exception($error);
                }
            }
            // Add the book author link
            $sql = 'insert into books_authors_link(book, author) values(:idBook, :idAuthor)';
            $stmt = $this->mDb->prepare($sql);
            $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
            $stmt->bindParam(':idAuthor', $idAuthor, PDO::PARAM_INT);
            $stmt->execute();
        }
        // Add the book language
        {
            // Get the language id
            $sql = 'select id from languages where lang_code=:language';
            $stmt = $this->mDb->prepare($sql);
            $stmt->bindParam(':language', $inBookInfo->mLanguage);
            $stmt->execute();
            $post = $stmt->fetchObject();
            if ($post) {
                $idLanguage = $post->id;
            } else {
                // Add a new language
                $sql = 'insert into languages(lang_code) values(:language)';
                $stmt = $this->mDb->prepare($sql);
                $stmt->bindParam(':language', $inBookInfo->mLanguage);
                $stmt->execute();
                // Get the language id
                $sql = 'select id from languages where lang_code=:language';
                $stmt = $this->mDb->prepare($sql);
                $stmt->bindParam(':language', $inBookInfo->mLanguage);
                $stmt->execute();
                $idLanguage = null;
                while ($post = $stmt->fetchObject()) {
                    if (!isset($idLanguage)) {
                        $idLanguage = $post->id;
                    } else {
                        $error = sprintf('Multiple languages for lang_code: %s', $inBookInfo->mLanguage);
                        throw new Exception($error);
                    }
                }
                if (!isset($idLanguage)) {
                    $error = sprintf('Cannot find language id for lang_code: %s', $inBookInfo->mLanguage);
                    throw new Exception($error);
                }
            }
            // Add the book language link
            $itemOder = 0;
            $sql = 'insert into books_languages_link(book, lang_code, item_order) values(:idBook, :idLanguage, :itemOrder)';
            $stmt = $this->mDb->prepare($sql);
            $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
            $stmt->bindParam(':idLanguage', $idLanguage, PDO::PARAM_INT);
            $stmt->bindParam(':itemOrder', $itemOder, PDO::PARAM_INT);
            $stmt->execute();
        }
        // Add the book tags (subjects)
        foreach ($inBookInfo->mSubjects as $subject) {
            // Get the subject id
            $sql = 'select id from tags where name=:subject';
            $stmt = $this->mDb->prepare($sql);
            $stmt->bindParam(':subject', $subject);
            $stmt->execute();
            $post = $stmt->fetchObject();
            if ($post) {
                $idSubject = $post->id;
            } else {
                // Add a new subject
                if (!empty($sortField)) {
                    $sql = sprintf('insert into tags(name, %s) values(:subject, :%s)', $sortField, $sortField);
                } else {
                    $sql = 'insert into tags(name) values(:subject)';
                }
                $stmt = $this->mDb->prepare($sql);
                $stmt->bindParam(':subject', $subject);
                // Add :sort field
                if (!empty($sortField)) {
                    $sortString = BookInfos::GetSortString($subject);
                    $stmt->bindParam(':' . $sortField, $sortString);
                }
                $stmt->execute();
                // Get the subject id
                $sql = 'select id from tags where name=:subject';
                $stmt = $this->mDb->prepare($sql);
                $stmt->bindParam(':subject', $subject);
                $stmt->execute();
                $idSubject = null;
                while ($post = $stmt->fetchObject()) {
                    if (!isset($idSubject)) {
                        $idSubject = $post->id;
                    } else {
                        $error = sprintf('Multiple subjects for name: %s', $subject);
                        throw new Exception($error);
                    }
                }
                if (!isset($idSubject)) {
                    $error = sprintf('Cannot find subject id for name: %s', $subject);
                    throw new Exception($error);
                }
            }
            // Add the book subject link
            $sql = 'insert into books_tags_link(book, tag) values(:idBook, :idSubject)';
            $stmt = $this->mDb->prepare($sql);
            $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
            $stmt->bindParam(':idSubject', $idSubject, PDO::PARAM_INT);
            $stmt->execute();
        }
        // Send warnings
        if (count($errors)) {
            $error = implode(' - ', $errors);
            throw new Exception($error);
        }
    }

    /**
     * Check database for debug
     *
     * @return void
     */
    protected function CheckDatabase()
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
        switch ($type) {
            case 'google':
                $url = Metadata\Sources\GoogleBooksMatch::link($value);
                break;
            case 'wd':
                $url = Metadata\Sources\WikiDataMatch::link($value);
                break;
            case 'olid':
                $url = Metadata\Sources\OpenLibraryMatch::link($value);
                break;
            default:
                $url = '';
        }
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
        if (file_exists(dirname($this->mDbFileName) . '/.calnotes/notes.db')) {
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
        $notesFileName = dirname($this->mDbFileName) . '/.calnotes/notes.db';
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
        $resourceDir = dirname($this->mDbFileName) . '/.calnotes/resources';
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
