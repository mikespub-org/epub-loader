<?php
/**
 * HasBooksTrait trait
 */

namespace Marsender\EPubLoader\Models;

/**
 * Deal with books property
 */
trait HasBooksTrait
{
    /** @var array<BookInfo> */
    public array $books = [];

    /**
     * Summary of addBook
     * @param mixed $bookId
     * @param array<mixed> $info
     * @return BookInfo
     */
    public function addBook($bookId, $info)
    {
        $bookInfo = BookInfo::load($this->basePath, $info);
        if (empty($bookId)) {
            $bookId = count($this->books);
        }
        $this->books[$bookId] = $bookInfo;
        return $bookInfo;
    }

    /**
     * Set titles for books - @todo
     * @param array<string> $bookList
     * @return self
     */
    public function setBookTitles($bookList)
    {
        foreach ($this->books as $id => $book) {
            if (empty($book->id) ||
                $book->id != $book->title ||
                empty($bookList[$book->id])) {
                continue;
            }
            $book->title = $bookList[$book->id];
            $book->sort = SeriesInfo::getTitleSort($book->title);
            $this->books[$id] = $book;
        }
        return $this;
    }

    /**
     * Set names for authors & titles for series in books
     * @param array<string> $authorList
     * @param array<string> $seriesList
     * @return self
     */
    public function fixBooks($authorList, $seriesList)
    {
        foreach ($this->books as $id => $book) {
            $book->setAuthorNames($authorList);
            $book->setSeriesTitles($seriesList);
            $this->books[$id] = $book;
        }
        return $this;
    }
}
