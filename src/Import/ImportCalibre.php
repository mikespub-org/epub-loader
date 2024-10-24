<?php
/**
 * ImportCalibre class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\BookInfo;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsMatch;
use Marsender\EPubLoader\Metadata\OpenLibrary\OpenLibraryMatch;
use Marsender\EPubLoader\Metadata\WikiData\WikiDataMatch;
use PDO;
use Exception;

class ImportCalibre extends ImportTarget
{
    /** @var array<int, int> */
    protected $ratingIndex = [];

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
    public function addBook($bookInfo, $bookId, $sortField = 'sort')
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
        $idBook = $this->addBookEntry($bookInfo, $bookId);
        if ($bookId && $idBook != $bookId) {
            $error = sprintf('Incorrect book id=%d vs %d for uuid: %s', $idBook, $bookId, $bookInfo->uuid);
            throw new Exception($error);
        }
        // Add the book data (formats)
        $this->addBookData($bookInfo, $idBook);
        // Add the book comments
        $this->addBookComments($bookInfo, $idBook);
        // Add the book identifiers
        $this->addBookIdentifiers($bookInfo, $idBook);
        // Add the book serie
        $this->addBookSeries($bookInfo, $idBook);
        // Add the book authors
        $this->addBookAuthors($bookInfo, $idBook);
        // Add the book language
        $this->addBookLanguage($bookInfo, $idBook);
        // Add the book tags (subjects)
        $this->addBookTags($bookInfo, $idBook, $sortField);
        // Add the book rating (if any)
        $this->addBookRating($bookInfo, $idBook);
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
     * @return int|null
     */
    public function addBookEntry($bookInfo, $bookId)
    {
        // Add the book
        $sql = 'insert into books(';
        if ($bookId) {
            $sql .= 'id, ';
        }
        // Add 'calibre_database_field_cover' field for books
        $sql .= 'title, sort, timestamp, pubdate, last_modified, series_index, uuid, path, has_cover, cover, isbn) values(';
        if ($bookId) {
            $sql .= ':id, ';
        }
        $sql .= ':title, :sort, :timestamp, :pubdate, :lastmodified, :serieindex, :uuid, :path, :hascover, :cover, :isbn)';
        $timeStamp = BookInfo::getSqlDate($bookInfo->timeStamp);
        $pubDate = BookInfo::getSqlDate(empty($bookInfo->creationDate) ? '2000-01-01 00:00:00' : $bookInfo->creationDate);
        $lastModified = BookInfo::getSqlDate(empty($bookInfo->modificationDate) ? '2000-01-01 00:00:00' : $bookInfo->modificationDate);
        $hasCover = empty($bookInfo->cover) ? 0 : 1;
        if (empty($bookInfo->cover)) {
            //$error = 'Warning: Cover not found';
            //$errors[] = $error;
            $cover = "";
        } else {
            $cover = str_replace('OEBPS/', $bookInfo->name . '/', $bookInfo->cover);
        }
        $stmt = $this->db->prepare($sql);
        if ($bookId) {
            $stmt->bindParam(':id', $bookId);
        }
        $stmt->bindParam(':title', $bookInfo->title);
        $sortString = BookInfo::getTitleSort($bookInfo->title);
        $sortString = BookInfo::getSortString($sortString);
        $stmt->bindParam(':sort', $sortString);
        $stmt->bindParam(':timestamp', $timeStamp);
        $stmt->bindParam(':pubdate', $pubDate);
        $stmt->bindParam(':lastmodified', $lastModified);
        $stmt->bindParam(':serieindex', $bookInfo->serieIndex);
        $stmt->bindParam(':uuid', $bookInfo->uuid);
        $stmt->bindParam(':path', $bookInfo->path);
        $stmt->bindParam(':hascover', $hasCover, PDO::PARAM_INT);
        $stmt->bindParam(':cover', $cover);
        $stmt->bindParam(':isbn', $bookInfo->isbn);
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
        foreach ($formats as $format) {
            if (str_contains($bookInfo->path, '://')) {
                continue;
            }
            $fileName = sprintf('%s%s%s%s%s.%s', $bookInfo->basePath, DIRECTORY_SEPARATOR, $bookInfo->path, DIRECTORY_SEPARATOR, $bookInfo->name, $format);
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
            $stmt->bindParam(':name', $bookInfo->name);
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
            $identifiers = array_merge($identifiers, $bookInfo->identifiers);
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
     * Summary of addBookSeries
     * @param BookInfo $bookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookSeries($bookInfo, $idBook)
    {
        if (empty($bookInfo->serie)) {
            return;
        }
        $link = '';
        if (!empty($bookInfo->serieIds) && !empty($bookInfo->serieIds[0]) && $bookInfo->serieIds[0] != $bookInfo->serie) {
            $link = match ($bookInfo->source) {
                'goodreads' => GoodReadsMatch::SERIES_URL . $bookInfo->serieIds[0],
                'wikidata' => WikiDataMatch::link($bookInfo->serieIds[0]),
                // @todo other sources?
                default => $bookInfo->serieIds[0],
            };
        }
        $idSerie = $this->addSeries($bookInfo->serie, $link);

        // Add the book serie link
        $sql = 'replace into books_series_link(book, series) values(:idBook, :idSerie)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
        $stmt->bindParam(':idSerie', $idSerie, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Summary of addSeries
     * @param string $serie series name
     * @param string|null $link series link (if available)
     * @return int
     */
    public function addSeries($serie, $link = null)
    {
        // Get the serie id
        $sql = 'select id from series where name=:serie';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':serie', $serie);
        $stmt->execute();
        $post = $stmt->fetchObject();
        if ($post) {
            $idSerie = $post->id;
            return $idSerie;
        }
        // Add a new serie
        $sql = 'insert into series(name, sort, link) values(:serie, :sort, :link)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':serie', $serie);
        $sortString = BookInfo::getTitleSort($serie);
        $sortString = BookInfo::getSortString($sortString);
        $stmt->bindParam(':sort', $sortString);
        $link ??= '';
        $stmt->bindParam(':link', $link);
        $stmt->execute();
        // Get the serie id
        $sql = 'select id from series where name=:serie';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':serie', $serie);
        $stmt->execute();
        $idSerie = null;
        while ($post = $stmt->fetchObject()) {
            if (!isset($idSerie)) {
                $idSerie = $post->id;
            } else {
                $error = sprintf('Multiple series for name: %s', $serie);
                throw new Exception($error);
            }
        }
        if (!isset($idSerie)) {
            $error = sprintf('Cannot find serie id for name: %s', $serie);
            throw new Exception($error);
        }
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
        $bookInfo->authorIds ??= [];
        $idx = 0;
        foreach ($bookInfo->authors as $authorSort => $author) {
            $link = '';
            if (count($bookInfo->authorIds) > $idx && !empty($bookInfo->authorIds[$idx]) && $bookInfo->authorIds[$idx] != $author) {
                $link = match ($bookInfo->source) {
                    'goodreads' => GoodReadsMatch::AUTHOR_URL . $bookInfo->authorIds[$idx],
                    'openlibrary' => OpenLibraryMatch::AUTHOR_URL . $bookInfo->authorIds[$idx],
                    'wikidata' => WikiDataMatch::link($bookInfo->authorIds[$idx]),
                    // @todo other sources?
                    default => $bookInfo->authorIds[$idx],
                };
            }
            $idAuthor = $this->addAuthor($author, $authorSort, $link);
            $idx++;

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
     * @param string $author
     * @param string $authorSort
     * @param string|null $authorLink
     * @return int
     */
    public function addAuthor($author, $authorSort, $authorLink = null)
    {
        // Get the author id
        $sql = 'select id from authors where name=:author';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':author', $author);
        $stmt->execute();
        $post = $stmt->fetchObject();
        if ($post) {
            $idAuthor = $post->id;
            return $idAuthor;
        }
        // Add a new author
        $sql = 'insert into authors(name, sort, link) values(:author, :sort, :link)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':author', $author);
        $sortString = BookInfo::getSortString($authorSort);
        $stmt->bindParam(':sort', $sortString);
        $link = $authorLink ?? '';
        $stmt->bindParam(':link', $link);
        $stmt->execute();
        // Get the author id
        $sql = 'select id from authors where name=:author';
        $stmt = $this->db->prepare($sql);
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
        return $idAuthor;
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
