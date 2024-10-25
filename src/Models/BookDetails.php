<?php
/**
 * BookDetails class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Models;

use PDO;

/**
 * BookDetails class gets all book details from the database
 */
class BookDetails
{
    /** @var PDO|null */
    protected $db = null;

    /**
     * Set database connection
     *
     * @param PDO $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Summary of getBookDetails
     * @param int $bookId
     * @return array<mixed>|null
     */
    public function getBookDetails($bookId)
    {
        $book = $this->getBookFields($bookId);
        $book['language'] = $this->getLanguage($bookId);
        $book['description'] = $this->getDescription($bookId);
        $book['rating'] = $this->getRating($bookId);
        $book['publisher'] = $this->getPublisher($bookId);
        $book['subjects'] = $this->getSubjects($bookId);
        $book['authors'] = $this->getAuthors($bookId);
        $book['series'] = $this->getSeries($bookId);
        if (!empty($book['series_index']) && !empty($book['series'])) {
            // set index for the first series found
            $firstId = array_key_first($book['series']);
            $book['series'][$firstId]['index'] = $book['series_index'];
        }
        $book['formats'] = $this->getFormats($bookId);
        $book['identifiers'] = $this->getIdentifiers($bookId);
        return $book;
    }

    /**
     * Summary of getBookFields
     * @param int $bookId
     * @return array<mixed>|null
     */
    public function getBookFields($bookId)
    {
        // Get books fields
        // id, title, sort, timestamp, pubdate, series_index, author_sort, isbn, lccn, path, flags, uuid, has_cover, last_modified
        // with optional extra 'cover' field for databases created here
        $sql = 'select * from books where id = ?';
        $params = [];
        $params[] = $bookId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $book = null;
        if ($post = $stmt->fetchObject()) {
            $book = (array) $post;
        }
        return $book;
    }

    /**
     * Summary of getLanguage
     * @param int $bookId
     * @return string|null
     */
    public function getLanguage($bookId)
    {
        // Get language
        $sql = 'select book, languages.id as lang_id, languages.lang_code as lang_code
        from books_languages_link left join languages on books_languages_link.lang_code = languages.id
        where book = ?';
        $params = [];
        $params[] = $bookId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $language = null;
        if ($post = $stmt->fetchObject()) {
            $language = $post->lang_code;
        }
        return $language;
    }

    /**
     * Summary of getDescription
     * @param int $bookId
     * @return string|null
     */
    public function getDescription($bookId)
    {
        // Get description
        $sql = 'select id, book, text from comments where book = ?';
        $params = [];
        $params[] = $bookId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $description = null;
        if ($post = $stmt->fetchObject()) {
            $description = $post->text;
        }
        return $description;
    }

    /**
     * Summary of getRating
     * @param int $bookId
     * @return float|null
     */
    public function getRating($bookId)
    {
        // Get rating
        $sql = 'select book, ratings.id as rating_id, ratings.rating as value
        from books_ratings_link left join ratings on books_ratings_link.rating = ratings.id
        where book = ?';
        $params = [];
        $params[] = $bookId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rating = null;
        if ($post = $stmt->fetchObject()) {
            $rating = (float) $post->value;
            $rating = $rating / 2.0;
        }
        return $rating;
    }

    /**
     * Summary of getPublisher
     * @param int $bookId
     * @return string|null
     */
    public function getPublisher($bookId)
    {
        // Get publisher
        $sql = 'select book, publishers.id as publisher_id, publishers.name as publisher
        from books_publishers_link left join publishers on books_publishers_link.publisher = publishers.id
        where book = ?';
        $params = [];
        $params[] = $bookId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $publisher = null;
        if ($post = $stmt->fetchObject()) {
            $publisher = $post->publisher;
        }
        return $publisher;
    }

    /**
     * Summary of getSubjects
     * @param int $bookId
     * @return array<string>
     */
    public function getSubjects($bookId)
    {
        // Get tags (subjects)
        $sql = 'select book, tags.id as tag_id, tags.name as tag
        from books_tags_link left join tags on books_tags_link.tag = tags.id
        where book = ?';
        $params = [];
        $params[] = $bookId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $subjects = [];
        while ($post = $stmt->fetchObject()) {
            $subjects[] = $post->tag;
        }
        return $subjects;
    }

    /**
     * Summary of getAuthors
     * @param int $bookId
     * @return array<mixed>
     */
    public function getAuthors($bookId)
    {
        // Get authors
        $sql = 'select book, authors.id as author_id, authors.name as name, authors.sort as sort, authors.link as link
        from books_authors_link left join authors on books_authors_link.author = authors.id
        where book = ?';
        $params = [];
        $params[] = $bookId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $authors = [];
        while ($post = $stmt->fetchObject()) {
            $authors[$post->author_id] = [
                'id' => $post->author_id,
                'name' => $post->name,
                'sort' => $post->sort,
                'link' => $post->link,
            ];
        }
        return $authors;
    }

    /**
     * Summary of getSeries
     * @param int $bookId
     * @return array<mixed>
     */
    public function getSeries($bookId)
    {
        // Get series
        $sql = 'select book, series.id as series_id, series.name as name, series.sort as sort, series.link as link
        from books_series_link left join series on books_series_link.series = series.id
        where book = ?';
        $params = [];
        $params[] = $bookId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $series = [];
        while ($post = $stmt->fetchObject()) {
            $series[$post->series_id] = [
                'id' => $post->series_id,
                'name' => $post->name,
                'sort' => $post->sort,
                'link' => $post->link,
                //'index' => '',
            ];
        }
        return $series;
    }

    /**
     * Summary of getFormats
     * @param int $bookId
     * @return array<mixed>
     */
    public function getFormats($bookId)
    {
        // Get data (formats)
        $sql = 'select id, book, format, name, uncompressed_size
        from data
        where book = ?';
        $params = [];
        $params[] = $bookId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $formats = [];
        while ($post = $stmt->fetchObject()) {
            $formats[$post->format] = [
                'id' => $post->id,
                'book' => $post->book,
                'format' => $post->format,
                'name' => $post->name,
                'size' => $post->uncompressed_size,
            ];
        }
        return $formats;
    }

    /**
     * Summary of getIdentifiers
     * @param int $bookId
     * @return array<mixed>
     */
    public function getIdentifiers($bookId)
    {
        // Get identifiers
        $sql = 'select id, book, type, val
        from identifiers
        where book = ?';
        $params = [];
        $params[] = $bookId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $identifiers = [];
        while ($post = $stmt->fetchObject()) {
            $identifiers[$post->type] = [
                'id' => $post->id,
                'book' => $post->book,
                'type' => $post->type,
                'value' => $post->val,
            ];
        }
        return $identifiers;
    }
}
