<?php
/**
 * BookImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\BookInfos;
use Marsender\EPubLoader\Metadata\BookLoadTrait;
use PDO;
use Exception;

class BookImport extends BaseImport
{
    use BookLoadTrait;

    protected string $mLabel = 'Load database';

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
    public function addBook($inBookInfo, $inBookId, $sortField = 'sort')
    {
        $errors = [];

        // Check if the book uuid does not already exist
        $error = $this->checkBookUuid($inBookInfo);
        if ($error) {
            $errors[] = $error;
            // Set a new uuid
            $inBookInfo->createUuid();
        }
        // Add the book
        $idBook = $this->addBookEntry($inBookInfo, $inBookId);
        if ($inBookId && $idBook != $inBookId) {
            $error = sprintf('Incorrect book id=%d vs %d for uuid: %s', $idBook, $inBookId, $inBookInfo->mUuid);
            throw new Exception($error);
        }
        // Add the book data (formats)
        $this->addBookData($inBookInfo, $idBook);
        // Add the book comments
        $this->addBookComments($inBookInfo, $idBook);
        // Add the book identifiers
        $this->addBookIdentifiers($inBookInfo, $idBook);
        // Add the book serie
        $this->addBookSeries($inBookInfo, $idBook);
        // Add the book authors
        $this->addBookAuthors($inBookInfo, $idBook);
        // Add the book language
        $this->addBookLanguage($inBookInfo, $idBook);
        // Add the book tags (subjects)
        $this->addBookTags($inBookInfo, $idBook, $sortField);
        // Send warnings
        if (count($errors)) {
            $error = implode(' - ', $errors);
            throw new Exception($error);
        }
    }

    /**
     * Summary of checkBookUuid
     * @param BookInfos $inBookInfo BookInfo object
     * @return string
     */
    public function checkBookUuid($inBookInfo)
    {
        $error = '';
        $sql = 'select b.id, b.title, b.path, d.name, d.format from books as b, data as d where d.book = b.id and uuid=:uuid';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':uuid', $inBookInfo->mUuid);
        $stmt->execute();
        while ($post = $stmt->fetchObject()) {
            $error = sprintf('Warning: Multiple book id for uuid: %s (already in file "%s/%s.%s" title "%s")', $inBookInfo->mUuid, $post->path, $post->name, $inBookInfo->mFormat, $post->title);
            break;
        }
        return $error;
    }

    /**
     * Summary of addBookEntry
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $inBookId Book id in the calibre db (or 0 for auto incrementation)
     * @return int|null
     */
    public function addBookEntry($inBookInfo, $inBookId)
    {
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
        $timeStamp = BookInfos::getSqlDate($inBookInfo->mTimeStamp);
        $pubDate = BookInfos::getSqlDate(empty($inBookInfo->mCreationDate) ? '2000-01-01 00:00:00' : $inBookInfo->mCreationDate);
        $lastModified = BookInfos::getSqlDate(empty($inBookInfo->mModificationDate) ? '2000-01-01 00:00:00' : $inBookInfo->mModificationDate);
        $hasCover = empty($inBookInfo->mCover) ? 0 : 1;
        if (empty($inBookInfo->mCover)) {
            //$error = 'Warning: Cover not found';
            //$errors[] = $error;
            $cover = "";
        } else {
            $cover = str_replace('OEBPS/', $inBookInfo->mName . '/', $inBookInfo->mCover);
        }
        $stmt = $this->mDb->prepare($sql);
        if ($inBookId) {
            $stmt->bindParam(':id', $inBookId);
        }
        $stmt->bindParam(':title', $inBookInfo->mTitle);
        $sortString = BookInfos::getSortString($inBookInfo->mTitle);
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
        return $idBook;
    }

    /**
     * Summary of addBookData (formats)
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookData($inBookInfo, $idBook)
    {
        $formats = [
            $inBookInfo->mFormat,
            'pdf',
        ];
        foreach ($formats as $format) {
            if (str_contains($inBookInfo->mPath, '://')) {
                continue;
            }
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
    }

    /**
     * Summary of addBookComments
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookComments($inBookInfo, $idBook)
    {
        $sql = 'insert into comments(book, text) values(:idBook, :text)';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
        $stmt->bindParam(':text', $inBookInfo->mDescription);
        $stmt->execute();
    }

    /**
     * Summary of addBookIdentifiers
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookIdentifiers($inBookInfo, $idBook)
    {
        if (empty($inBookInfo->mUri)) {
            return;
        }
        $sql = 'insert into identifiers(book, type, val) values(:idBook, :type, :value)';
        $identifiers = [];
        $identifiers['url'] = $inBookInfo->mUri;
        $identifiers['isbn'] = $inBookInfo->mIsbn;
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

    /**
     * Summary of addBookSeries
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookSeries($inBookInfo, $idBook)
    {
        if (empty($inBookInfo->mSerie)) {
            return;
        }
        $idSerie = $this->addSeries($inBookInfo->mSerie);

        // Add the book serie link
        $sql = 'insert into books_series_link(book, series) values(:idBook, :idSerie)';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
        $stmt->bindParam(':idSerie', $idSerie, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Summary of addSeries
     * @param string $inSerie series name
     * @return int
     */
    public function addSeries($inSerie)
    {
        // Get the serie id
        $sql = 'select id from series where name=:serie';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':serie', $inSerie);
        $stmt->execute();
        $post = $stmt->fetchObject();
        if ($post) {
            $idSerie = $post->id;
            return $idSerie;
        }
        // Add a new serie
        $sql = 'insert into series(name, sort) values(:serie, :sort)';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':serie', $inSerie);
        $sortString = BookInfos::getSortString($inSerie);
        $stmt->bindParam(':sort', $sortString);
        $stmt->execute();
        // Get the serie id
        $sql = 'select id from series where name=:serie';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':serie', $inSerie);
        $stmt->execute();
        $idSerie = null;
        while ($post = $stmt->fetchObject()) {
            if (!isset($idSerie)) {
                $idSerie = $post->id;
            } else {
                $error = sprintf('Multiple series for name: %s', $inSerie);
                throw new Exception($error);
            }
        }
        if (!isset($idSerie)) {
            $error = sprintf('Cannot find serie id for name: %s', $inSerie);
            throw new Exception($error);
        }
        return $idSerie;
    }

    /**
     * Summary of addBookAuthors
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookAuthors($inBookInfo, $idBook)
    {
        foreach ($inBookInfo->mAuthors as $authorSort => $author) {
            $idAuthor = $this->addAuthor($author, $authorSort);

            // Add the book author link
            $sql = 'insert into books_authors_link(book, author) values(:idBook, :idAuthor)';
            $stmt = $this->mDb->prepare($sql);
            $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
            $stmt->bindParam(':idAuthor', $idAuthor, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    /**
     * Summary of addAuthor
     * @param string $author
     * @param string $authorSort
     * @return int
     */
    public function addAuthor($author, $authorSort)
    {
        // Get the author id
        $sql = 'select id from authors where name=:author';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':author', $author);
        $stmt->execute();
        $post = $stmt->fetchObject();
        if ($post) {
            $idAuthor = $post->id;
            return $idAuthor;
        }
        // Add a new author
        $sql = 'insert into authors(name, sort) values(:author, :sort)';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':author', $author);
        $sortString = BookInfos::getSortString($authorSort);
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
        return $idAuthor;
    }

    /**
     * Summary of addBookLanguage
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @throws \Exception
     * @return void
     */
    public function addBookLanguage($inBookInfo, $idBook)
    {
        $idLanguage = $this->addLanguage($inBookInfo->mLanguage);

        // Add the book language link
        $itemOder = 0;
        $sql = 'insert into books_languages_link(book, lang_code, item_order) values(:idBook, :idLanguage, :itemOrder)';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
        $stmt->bindParam(':idLanguage', $idLanguage, PDO::PARAM_INT);
        $stmt->bindParam(':itemOrder', $itemOder, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Summary of addLanguage
     * @param string $inLanguage
     * @return int
     */
    public function addLanguage($inLanguage)
    {
        // Get the language id
        $sql = 'select id from languages where lang_code=:language';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':language', $inLanguage);
        $stmt->execute();
        $post = $stmt->fetchObject();
        if ($post) {
            $idLanguage = $post->id;
            return $idLanguage;
        }
        // Add a new language
        $sql = 'insert into languages(lang_code) values(:language)';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':language', $inLanguage);
        $stmt->execute();
        // Get the language id
        $sql = 'select id from languages where lang_code=:language';
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':language', $inLanguage);
        $stmt->execute();
        $idLanguage = null;
        while ($post = $stmt->fetchObject()) {
            if (!isset($idLanguage)) {
                $idLanguage = $post->id;
            } else {
                $error = sprintf('Multiple languages for lang_code: %s', $inLanguage);
                throw new Exception($error);
            }
        }
        if (!isset($idLanguage)) {
            $error = sprintf('Cannot find language id for lang_code: %s', $inLanguage);
            throw new Exception($error);
        }
        return $idLanguage;
    }

    /**
     * Summary of addBookTags (subjects)
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $idBook Book id in the calibre db
     * @param string $sortField Add 'calibre_database_field_sort' field
     * @throws \Exception
     * @return void
     */
    public function addBookTags($inBookInfo, $idBook, $sortField = 'sort')
    {
        if (empty($inBookInfo->mSubjects)) {
            return;
        }
        foreach ($inBookInfo->mSubjects as $subject) {
            $idSubject = $this->addTag($subject, $sortField);

            // Add the book subject link
            $sql = 'insert into books_tags_link(book, tag) values(:idBook, :idSubject)';
            $stmt = $this->mDb->prepare($sql);
            $stmt->bindParam(':idBook', $idBook, PDO::PARAM_INT);
            $stmt->bindParam(':idSubject', $idSubject, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    /**
     * Summary of addTag
     * @param string $subject
     * @param string $sortField Add 'calibre_database_field_sort' field
     * @return int
     */
    public function addTag($subject, $sortField = 'sort')
    {
        // Get the subject id
        $sql = 'select id from tags where name=:subject';
        $stmt = $this->mDb->prepare($sql);
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
        $stmt = $this->mDb->prepare($sql);
        $stmt->bindParam(':subject', $subject);
        // Add :sort field
        if (!empty($sortField)) {
            $sortString = BookInfos::getSortString($subject);
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
        return $idSubject;
    }
}
