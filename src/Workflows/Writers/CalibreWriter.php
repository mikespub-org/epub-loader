<?php

/**
 * CalibreWriter class
 */

namespace Marsender\EPubLoader\Workflows\Writers;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\NoteInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsMatch;
use Marsender\EPubLoader\Metadata\OpenLibrary\OpenLibraryMatch;
use Marsender\EPubLoader\Metadata\WikiData\WikiDataMatch;
use PDO;
use Exception;

class CalibreWriter extends DatabaseWriter
{
    /** @var array<int, int> */
    protected $ratingIndex = [];

    /**
     * Add a new book into the db
     *
     * @param BookInfo $bookInfo BookInfo object
     * @param int $bookId Book id in the calibre db (or 0 for auto incrementation)
     * @param string $sortField Add 'calibre_database_field_sort' field for tags
     * @param string $coverField Add 'calibre_database_field_cover' field for books
     *
     * @throws Exception if error
     *
     * @return void
     */
    public function addBook($bookInfo, $bookId = 0, $sortField = 'sort', $coverField = 'cover')
    {
        $errors = [];

        // Check if the book uuid does not already exist
        $error = $this->checkBookUuid($bookInfo);
        if ($error) {
            $errors[] = $error;
            // Set a new uuid
            $bookInfo->createUuid();
        }
        // Add the book
        $idBook = $this->addBookEntry($bookInfo, $bookId, $coverField);
        if ($bookId && $idBook != $bookId) {
            $error = sprintf('Incorrect book id=%d vs %d for uuid: %s', $idBook, $bookId, $bookInfo->uuid);
            throw new Exception($error);
        }
        // Add the book data (formats)
        try {
            $this->addBookData($bookInfo, $idBook);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        // Add the book comments
        try {
            $this->addBookComments($bookInfo, $idBook);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        // Add the book identifiers
        try {
            $this->addBookIdentifiers($bookInfo, $idBook);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        // Add the book serie
        try {
            $this->addBookSeries($bookInfo, $idBook);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        // Add the book authors
        try {
            $this->addBookAuthors($bookInfo, $idBook);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        // Add the book language
        try {
            $this->addBookLanguage($bookInfo, $idBook);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        // Add the book tags (subjects)
        try {
            $this->addBookTags($bookInfo, $idBook, $sortField);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        // Add the book rating (if any)
        try {
            $this->addBookRating($bookInfo, $idBook);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        // Send warnings
        if (count($errors)) {
            $error = implode(' - ', $errors);
            throw new Exception($error);
        }
    }

    /**
     * Summary of checkBookUuid
     * @param BookInfo $bookInfo BookInfo object
     * @return string
     */
    public function checkBookUuid($bookInfo)
    {
        $error = '';
        $sql = 'select b.id, b.title, b.path, d.name, d.format from books as b, data as d where d.book = b.id and uuid=:uuid';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':uuid', $bookInfo->uuid);
        $stmt->execute();
        while ($post = $stmt->fetchObject()) {
            $error = sprintf('Warning: Multiple book id for uuid: %s (already in file "%s/%s.%s" title "%s")', $bookInfo->uuid, $post->path, $post->name, $bookInfo->format, $post->title);
            break;
        }
        return $error;
    }

    /**
     * Summary of addBookEntry
     * @param BookInfo $bookInfo BookInfo object
     * @param int $bookId Book id in the calibre db (or 0 for auto incrementation)
     * @param string $coverField Add 'calibre_database_field_cover' field for books
     * @return int|null
     */
    public function addBookEntry($bookInfo, $bookId, $coverField = 'cover')
    {
        // Add the book
        $fields = ['title', 'sort', 'timestamp', 'pubdate', 'last_modified', 'series_index', 'uuid', 'path', 'has_cover', 'isbn'];
        $params = [':title', ':sort', ':timestamp', ':pubdate', ':lastmodified', ':serieindex', ':uuid', ':path', ':hascover', ':isbn'];
        if ($bookId) {
            array_unshift($fields, 'id');
            array_unshift($params, ':id');
        }
        // Add 'calibre_database_field_cover' field for books
        if (!empty($coverField)) {
            array_push($fields, $coverField);
            array_push($params, ':cover');
        }
        $sql = 'insert into books(';
        $sql .= implode(', ', $fields);
        $sql .= ') values(';
        $sql .= implode(', ', $params);
        $sql .= ')';
        $timestamp = BookInfo::getSqlDate($bookInfo->timestamp);
        $pubDate = BookInfo::getSqlDate(empty($bookInfo->creationDate) ? '2000-01-01 00:00:00' : $bookInfo->creationDate);
        $lastModified = BookInfo::getSqlDate(empty($bookInfo->modificationDate) ? '2000-01-01 00:00:00' : $bookInfo->modificationDate);
        $hasCover = empty($bookInfo->cover) ? 0 : 1;
        $cover = '';
        if (empty($bookInfo->cover)) {
            //$error = 'Warning: Cover not found';
            //$errors[] = $error;
        } elseif (str_contains($bookInfo->cover, 'OEBPS/')) {
            // @todo when is this needed?
            $cover = str_replace('OEBPS/', $bookInfo->id . '/', $bookInfo->cover);
        }
        $stmt = $this->db->prepare($sql);
        if ($bookId) {
            $stmt->bindParam(':id', $bookId);
        }
        $stmt->bindParam(':title', $bookInfo->title);
        $sortString = BookInfo::getTitleSort($bookInfo->title);
        $sortString = BookInfo::getSortString($sortString);
        $stmt->bindParam(':sort', $sortString);
        $stmt->bindParam(':timestamp', $timestamp);
        $stmt->bindParam(':pubdate', $pubDate);
        $stmt->bindParam(':lastmodified', $lastModified);
        //$stmt->bindParam(':serieindex', $bookInfo->serieIndex);
        $seriesInfo = $bookInfo->getSeriesInfo();
        $stmt->bindParam(':serieindex', $seriesInfo->index);
        $stmt->bindParam(':uuid', $bookInfo->uuid);
        $stmt->bindParam(':path', $bookInfo->path);
        $stmt->bindParam(':hascover', $hasCover, PDO::PARAM_INT);
        $stmt->bindParam(':isbn', $bookInfo->isbn);
        if (!empty($coverField)) {
            $stmt->bindParam(':cover', $cover);
        }
        $stmt->execute();
        // Get the book id
        $sql = 'select id from books where uuid=:uuid';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':uuid', $bookInfo->uuid);
        $stmt->execute();
        $idBook = null;
        $post = $stmt->fetchObject();
        if ($post) {
            $idBook = $post->id;
        }
        if (empty($idBook)) {
            $error = sprintf('Cannot find book id for uuid: %s', $bookInfo->uuid);
            throw new Exception($error);
        }
        return $idBook;
    }

    /**
     * Update image for existing book
     * @param BookInfo $bookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @param string $coverField Add 'calibre_database_field_cover' field for books
     * @return int
     */
    public function setBookCover($bookInfo, $idBook, $coverField = 'cover')
    {
        if (empty($coverField)) {
            return $idBook;
        }
        $sql = 'update books set ' . $coverField . '=:cover where id=:id';
        try {
            $stmt = $this->db->prepare($sql);
        } catch (Exception $e) {
            return $idBook;
        }
        $stmt->bindParam(':cover', $bookInfo->cover);
        $stmt->bindParam(':id', $idBook, PDO::PARAM_INT);
        $stmt->execute();
        return $idBook;
    }

    /**
     * Summary of addBookData (formats)
     * @param BookInfo $bookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookData($bookInfo, $idBook)
    {
        $formats = [
            $bookInfo->format,
            'pdf',
        ];
        // When using GoodReadsImport() etc.:
        //   $bookInfo->path = $bookInfo->uri;
        //   $bookInfo->id = entity id
        if (str_contains($bookInfo->path, '://')) {
            return;
        }
        // When importing JSON records from previous json_dump
        if ($bookInfo->source == 'database' || is_numeric($bookInfo->id)) {
            return;
        }
        // When using LocalBooksImport():
        //   $bookInfo->path = pathinfo($fileName, PATHINFO_DIRNAME);
        //   $bookInfo->id = pathinfo($fileName, PATHINFO_FILENAME);
        $name = $bookInfo->id;
        foreach ($formats as $format) {
            $fileName = sprintf('%s%s%s%s%s.%s', $bookInfo->basePath, DIRECTORY_SEPARATOR, $bookInfo->path, DIRECTORY_SEPARATOR, $name, $format);
            if (!is_readable($fileName)) {
                if ($format == $bookInfo->format) {
                    $error = sprintf('Cannot read file: %s', $fileName);
                    throw new Exception($error);
                }
                continue;
            }
            $uncompressedSize = filesize($fileName);
            $sql = 'insert into data(book, format, name, uncompressed_size) values(:idBook, :format, :name, :uncompressedSize)';
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
            $format = strtoupper($format);
            $stmt->bindParam(':format', $format); // Calibre format is uppercase
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':uncompressedSize', $uncompressedSize);
            $stmt->execute();
        }
    }

    /**
     * Summary of addBookComments
     * @param BookInfo $bookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookComments($bookInfo, $idBook)
    {
        $sql = 'replace into comments(book, text) values(:idBook, :text)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
        $stmt->bindParam(':text', $bookInfo->description);
        $stmt->execute();
    }

    /**
     * Summary of addBookIdentifiers
     * @param BookInfo $bookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookIdentifiers($bookInfo, $idBook)
    {
        if (empty($bookInfo->uri) && empty($bookInfo->isbn) && empty($bookInfo->identifiers)) {
            return;
        }
        $sql = 'replace into identifiers(book, type, val) values(:idBook, :type, :value)';
        $identifiers = [];
        $identifiers['url'] = $bookInfo->uri;
        $identifiers['isbn'] = $bookInfo->isbn;
        if (!empty($bookInfo->identifiers)) {
            foreach ($bookInfo->identifiers as $type => $identifier) {
                if (is_array($identifier)) {
                    $identifiers[$type] ??= $identifier['value'];
                } else {
                    $identifiers[$type] ??= $identifier;
                }
            }
        }
        foreach ($identifiers as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
            $stmt->bindParam(':type', $key);
            $stmt->bindParam(':value', $value);
            $stmt->execute();
        }
    }

    /**
     * Update link for existing book
     * @param BookInfo $bookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @param string $type Identifier type used for book link
     * @return int
     */
    public function setBookUri($bookInfo, $idBook, $type = 'url')
    {
        if (empty($type)) {
            return $idBook;
        }
        $sql = 'replace into identifiers(book, type, val) values(:idBook, :type, :value)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':value', $bookInfo->uri);
        $stmt->execute();
        return $idBook;
    }

    /**
     * Summary of addBookSeries
     * @param BookInfo $bookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookSeries($bookInfo, $idBook)
    {
        if (empty($bookInfo->series)) {
            return;
        }
        $seriesInfo = $bookInfo->getSeriesInfo();
        if (empty($seriesInfo->title)) {
            return;
        }
        if (empty($seriesInfo->link) && !empty($seriesInfo->id) && $seriesInfo->id != $seriesInfo->title) {
            $seriesInfo->link = match ($bookInfo->source) {
                'goodreads' => GoodReadsMatch::SERIES_URL . $seriesInfo->id,
                'wikidata' => WikiDataMatch::link($seriesInfo->id),
                // @todo other sources?
                default => $seriesInfo->id,
            };
        }
        $idSerie = $this->addSeries($seriesInfo);

        // Add the book serie link
        $sql = 'replace into books_series_link(book, series) values(:idBook, :idSerie)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
        $stmt->bindParam(':idSerie', $idSerie, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Summary of addSeries
     * @param SeriesInfo $seriesInfo
     * @param mixed $seriesId (not used)
     * @return int
     */
    public function addSeries($seriesInfo, $seriesId = 0)
    {
        // Get the serie id
        $sql = 'select id, link from series where name=:title';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':title', $seriesInfo->title);
        $stmt->execute();
        $post = $stmt->fetchObject();
        if ($post) {
            $idSerie = $post->id;
            if (!empty($seriesInfo->note) && !empty($seriesInfo->note->doc)) {
                // Add series note
                $seriesInfo->note->item = $idSerie;
                $this->addNote($seriesInfo->note);
            }
            if (!empty($seriesInfo->image)) {
                // @todo Update image for existing series
                $this->setSeriesImage($seriesInfo, $idSerie);
            }
            if (!empty($seriesInfo->link) && $seriesInfo->link != $post->link) {
                // Update link for existing series
                $this->setSeriesLink($seriesInfo, $idSerie);
            }
            return $idSerie;
        }
        // Add a new serie
        $sql = 'insert into series(name, sort, link) values(:title, :sort, :link)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':title', $seriesInfo->title);
        $sortString = BookInfo::getSortString($seriesInfo->sort);
        $stmt->bindParam(':sort', $sortString);
        $stmt->bindParam(':link', $seriesInfo->link);
        $stmt->execute();
        // Get the serie id
        $sql = 'select id from series where name=:title';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':title', $seriesInfo->title);
        $stmt->execute();
        $idSerie = null;
        while ($post = $stmt->fetchObject()) {
            if (!isset($idSerie)) {
                $idSerie = $post->id;
            } else {
                $error = sprintf('Multiple series for name: %s', $seriesInfo->title);
                throw new Exception($error);
            }
        }
        if (!isset($idSerie)) {
            $error = sprintf('Cannot find serie id for name: %s', $seriesInfo->title);
            throw new Exception($error);
        }
        if (!empty($seriesInfo->note) && !empty($seriesInfo->note->doc)) {
            // Add series note
            $seriesInfo->note->item = $idSerie;
            $this->addNote($seriesInfo->note);
        }
        if (!empty($seriesInfo->image)) {
            // @todo Update image for existing series
            $this->setSeriesImage($seriesInfo, $idSerie);
        }
        return $idSerie;
    }

    /**
     * Update image for existing series
     * @param SeriesInfo $seriesInfo
     * @param int $idSerie Series id in the calibre db
     * @param string $imageField Add 'calibre_database_field_image' field for series
     * @return int
     */
    public function setSeriesImage($seriesInfo, $idSerie, $imageField = 'image')
    {
        if (empty($imageField)) {
            return $idSerie;
        }
        // @todo add image field in series table?
        // @todo deal with 'after update' trigger with title_sort() - see DatabaseLoader::addSqliteFunctions()
        //$sql = $this->db->wrapTrigger($sql, 'series', 'AFTER UPDATE ON');
        $sql = 'update series set ' . $imageField . '=:image where id=:id';
        try {
            $stmt = $this->db->prepare($sql);
        } catch (Exception $e) {
            return $idSerie;
        }
        $stmt->bindParam(':image', $seriesInfo->image);
        $stmt->bindParam(':id', $idSerie, PDO::PARAM_INT);
        $stmt->execute();
        return $idSerie;
    }

    /**
     * Update link for existing series
     * @param SeriesInfo $seriesInfo
     * @param int $idSerie Series id in the calibre db
     * @return int
     */
    public function setSeriesLink($seriesInfo, $idSerie)
    {
        // @todo deal with 'after update' trigger with title_sort() - see DatabaseLoader::addSqliteFunctions()
        //$sql = $this->db->wrapTrigger($sql, 'series', 'AFTER UPDATE ON');
        $sql = 'update series set link=:link where id=:id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':link', $seriesInfo->link);
        $stmt->bindParam(':id', $idSerie, PDO::PARAM_INT);
        $stmt->execute();
        return $idSerie;
    }

    /**
     * Summary of addBookAuthors
     * @param BookInfo $bookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookAuthors($bookInfo, $idBook)
    {
        if (empty($bookInfo->authors)) {
            return;
        }
        foreach ($bookInfo->authors as $authorId => $authorInfo) {
            if (empty($authorInfo->link) && !empty($authorInfo->id) && $authorInfo->id != $authorInfo->name) {
                $authorInfo->link = match ($bookInfo->source) {
                    'goodreads' => GoodReadsMatch::AUTHOR_URL . $authorInfo->id,
                    'openlibrary' => OpenLibraryMatch::AUTHOR_URL . $authorInfo->id,
                    'wikidata' => WikiDataMatch::link($authorInfo->id),
                    // @todo other sources?
                    default => $authorInfo->id,
                };
            }
            $idAuthor = $this->addAuthor($authorInfo);

            // Add the book author link
            $sql = 'replace into books_authors_link(book, author) values(:idBook, :idAuthor)';
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
            $stmt->bindParam(':idAuthor', $idAuthor, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    /**
     * Summary of addAuthor
     * @param AuthorInfo $authorInfo
     * @param mixed $authorId (not used)
     * @return int
     */
    public function addAuthor($authorInfo, $authorId = 0)
    {
        // Get the author id
        $sql = 'select id, link from authors where name=:name';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $authorInfo->name);
        $stmt->execute();
        $post = $stmt->fetchObject();
        if ($post) {
            $idAuthor = $post->id;
            if (!empty($authorInfo->note) && !empty($authorInfo->note->doc)) {
                // Add author note
                $authorInfo->note->item = $idAuthor;
                $this->addNote($authorInfo->note);
            }
            if (!empty($authorInfo->image)) {
                // @todo Update image for existing author
                $this->setAuthorImage($authorInfo, $idAuthor);
            }
            if (!empty($authorInfo->link) && $authorInfo->link != $post->link) {
                // Update link for existing author
                $this->setAuthorLink($authorInfo, $idAuthor);
            }
            return $idAuthor;
        }
        // Add a new author
        $sql = 'insert into authors(name, sort, link) values(:name, :sort, :link)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $authorInfo->name);
        $sortString = BookInfo::getSortString($authorInfo->sort);
        $stmt->bindParam(':sort', $sortString);
        $stmt->bindParam(':link', $authorInfo->link);
        $stmt->execute();
        // Get the author id
        $sql = 'select id from authors where name=:name';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $authorInfo->name);
        $stmt->execute();
        $idAuthor = null;
        while ($post = $stmt->fetchObject()) {
            if (!isset($idAuthor)) {
                $idAuthor = $post->id;
            } else {
                $error = sprintf('Multiple authors for name: %s', $authorInfo->name);
                throw new Exception($error);
            }
        }
        if (!isset($idAuthor)) {
            $error = sprintf('Cannot find author id for name: %s', $authorInfo->name);
            throw new Exception($error);
        }
        if (!empty($authorInfo->note) && !empty($authorInfo->note->doc)) {
            // Add author note
            $authorInfo->note->item = $idAuthor;
            $this->addNote($authorInfo->note);
        }
        if (!empty($authorInfo->image)) {
            // @todo Update image for existing author
            $this->setAuthorImage($authorInfo, $idAuthor);
        }
        return $idAuthor;
    }

    /**
     * Update image for existing author
     * @param AuthorInfo $authorInfo
     * @param int $idAuthor Author id in the calibre db
     * @param string $imageField Add 'calibre_database_field_image' field for authors
     * @return int
     */
    public function setAuthorImage($authorInfo, $idAuthor, $imageField = 'image')
    {
        if (empty($imageField)) {
            return $idAuthor;
        }
        // @todo add image field in authors table?
        $sql = 'update authors set ' . $imageField . '=:image where id=:id';
        try {
            $stmt = $this->db->prepare($sql);
        } catch (Exception $e) {
            return $idAuthor;
        }
        $stmt->bindParam(':image', $authorInfo->image);
        $stmt->bindParam(':id', $idAuthor, PDO::PARAM_INT);
        $stmt->execute();
        return $idAuthor;
    }

    /**
     * Update link for existing author
     * @param AuthorInfo $authorInfo
     * @param int $idAuthor Author id in the calibre db
     * @return int
     */
    public function setAuthorLink($authorInfo, $idAuthor)
    {
        $sql = 'update authors set link=:link where id=:id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':link', $authorInfo->link);
        $stmt->bindParam(':id', $idAuthor, PDO::PARAM_INT);
        $stmt->execute();
        return $idAuthor;
    }

    /**
     * Summary of addNote
     * @param NoteInfo $note
     * @return int
     */
    public function addNote($note)
    {
        // Get the note id
        $sql = 'select id from notes_db.notes where colname=:colname and item=:item';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':colname', $note->colname);
        $stmt->bindParam(':item', $note->item, PDO::PARAM_INT);
        $stmt->execute();
        $post = $stmt->fetchObject();
        if ($post) {
            $idNote = $post->id;
            // Update existing note
            $sql = 'update notes_db.notes set doc=:doc where id=:id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':doc', $note->doc);
            $stmt->bindParam(':id', $idNote, PDO::PARAM_INT);
            $stmt->execute();
            return $idNote;
        }
        // Add a new note
        $sql = 'insert into notes_db.notes(colname, item, doc, mtime) values(:colname, :item, :doc, :mtime)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':colname', $note->colname);
        $stmt->bindParam(':item', $note->item, PDO::PARAM_INT);
        $stmt->bindParam(':doc', $note->doc);
        $stmt->bindParam(':mtime', $note->mtime, PDO::PARAM_INT);
        $stmt->execute();
        // Get the note id
        $sql = 'select id from notes_db.notes where colname=:colname and item=:item';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':colname', $note->colname);
        $stmt->bindParam(':item', $note->item, PDO::PARAM_INT);
        $stmt->execute();
        $idNote = null;
        while ($post = $stmt->fetchObject()) {
            if (!isset($idNote)) {
                $idNote = $post->id;
            } else {
                $error = sprintf('Multiple notes for colname: %s item: %s', $note->colname, $note->item);
                throw new Exception($error);
            }
        }
        if (!isset($idNote)) {
            $error = sprintf('Cannot find note id colname: %s item: %s', $note->colname, $note->item);
            throw new Exception($error);
        }
        return $idNote;
    }

    /**
     * Summary of addBookLanguage
     * @param BookInfo $bookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookLanguage($bookInfo, $idBook)
    {
        $idLanguage = $this->addLanguage($bookInfo->language);

        // Add the book language link
        $itemOder = 0;
        $sql = 'replace into books_languages_link(book, lang_code, item_order) values(:idBook, :idLanguage, :itemOrder)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
        $stmt->bindParam(':idLanguage', $idLanguage, PDO::PARAM_INT);
        $stmt->bindParam(':itemOrder', $itemOder, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Summary of addLanguage
     * @param string $language
     * @return int
     */
    public function addLanguage($language)
    {
        // Get the language id
        $sql = 'select id from languages where lang_code=:language';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':language', $language);
        $stmt->execute();
        $post = $stmt->fetchObject();
        if ($post) {
            $idLanguage = $post->id;
            return $idLanguage;
        }
        // Add a new language
        $sql = 'insert into languages(lang_code) values(:language)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':language', $language);
        $stmt->execute();
        // Get the language id
        $sql = 'select id from languages where lang_code=:language';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':language', $language);
        $stmt->execute();
        $idLanguage = null;
        while ($post = $stmt->fetchObject()) {
            if (!isset($idLanguage)) {
                $idLanguage = $post->id;
            } else {
                $error = sprintf('Multiple languages for lang_code: %s', $language);
                throw new Exception($error);
            }
        }
        if (!isset($idLanguage)) {
            $error = sprintf('Cannot find language id for lang_code: %s', $language);
            throw new Exception($error);
        }
        return $idLanguage;
    }

    /**
     * Summary of addBookTags (subjects)
     * @param BookInfo $bookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @param string $sortField Add 'calibre_database_field_sort' field for tags
     * @throws \Exception
     * @return void
     */
    public function addBookTags($bookInfo, $idBook, $sortField = 'sort')
    {
        if (empty($bookInfo->subjects)) {
            return;
        }
        foreach ($bookInfo->subjects as $subject) {
            $idSubject = $this->addTag($subject, $sortField);

            // Add the book subject link
            $sql = 'replace into books_tags_link(book, tag) values(:idBook, :idSubject)';
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
            $stmt->bindParam(':idSubject', $idSubject, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    /**
     * Summary of addTag
     * @param string $subject
     * @param string $sortField Add 'calibre_database_field_sort' field for tags
     * @return int
     */
    public function addTag($subject, $sortField = 'sort')
    {
        // Get the subject id
        $sql = 'select id from tags where name=:subject';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':subject', $subject);
        $stmt->execute();
        $post = $stmt->fetchObject();
        if ($post) {
            $idSubject = $post->id;
            return $idSubject;
        }
        // Add a new subject
        if (!empty($sortField)) {
            $sql = sprintf('insert into tags(name, %s) values(:subject, :%s)', $sortField, $sortField);
        } else {
            $sql = 'insert into tags(name) values(:subject)';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':subject', $subject);
        // Add :sort field
        if (!empty($sortField)) {
            $sortString = BookInfo::getTitleSort($subject);
            $sortString = BookInfo::getSortString($sortString);
            $stmt->bindParam(':' . $sortField, $sortString);
        }
        $stmt->execute();
        // Get the subject id
        $sql = 'select id from tags where name=:subject';
        $stmt = $this->db->prepare($sql);
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
        return $idSubject;
    }

    /**
     * Summary of addBookRating
     * @param BookInfo $bookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookRating($bookInfo, $idBook)
    {
        $idRating = $this->getRatingIndex($bookInfo->rating);
        if (empty($idRating)) {
            return;
        }

        // Add the book rating link
        $sql = 'replace into books_ratings_link(book, rating) values(:idBook, :idRating)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
        $stmt->bindParam(':idRating', $idRating, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Summary of getRatingIndex
     * @param float|int|null $rating
     * @return int
     */
    public function getRatingIndex($rating)
    {
        if (empty($rating)) {
            return 0;
        }
        // load mapping of rating to index
        if (count($this->ratingIndex) < 10) {
            $this->loadRatingIndex();
        }
        // switch to 0-10 rating
        $rating = (int) round($rating * 2.0);
        if (!isset($this->ratingIndex[$rating])) {
            return 0;
        }
        return $this->ratingIndex[$rating];
    }

    /**
     * Summary of loadRatingIndex
     * @throws \Exception
     * @return void
     */
    public function loadRatingIndex()
    {
        // load mapping of rating to index
        $sql = 'select id, rating from ratings';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        while ($post = $stmt->fetchObject()) {
            $this->ratingIndex[$post->rating] = $post->id;
        }
        if (count($this->ratingIndex) == 10) {
            return;
        }
        // add missing ratings (if any)
        $sql = 'insert into ratings(rating) values(:idRating)';
        $range = range(1, 10);
        foreach ($range as $rating) {
            if (isset($this->ratingIndex[$rating])) {
                continue;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':idRating', $rating, PDO::PARAM_INT);
            $stmt->execute();
        }
        // load mapping of rating to index
        $sql = 'select id, rating from ratings';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        while ($post = $stmt->fetchObject()) {
            $this->ratingIndex[$post->rating] = $post->id;
        }
        if (count($this->ratingIndex) != 10) {
            throw new Exception('Cannot create mapping of rating to index');
        }
    }
}
