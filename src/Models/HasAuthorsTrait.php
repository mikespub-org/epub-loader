<?php

/**
 * HasAuthorsTrait trait
 */

namespace Marsender\EPubLoader\Models;

/**
 * Deal with authors property
 */
trait HasAuthorsTrait
{
    /** @var array<AuthorInfo> */
    public $authors = [];

    /**
     * Summary of addAuthor
     * @param mixed $authorId
     * @param array<mixed> $info
     * @return AuthorInfo
     */
    public function addAuthor($authorId, $info)
    {
        $authorInfo = AuthorInfo::load($this->basePath, $info);
        if (empty($authorId)) {
            $authorId = count($this->authors);
        }
        $this->authors[$authorId] = $authorInfo;
        return $authorInfo;
    }

    /**
     * Summary of getAuthorNames
     * @return array<string>
     */
    public function getAuthorNames()
    {
        return array_column($this->authors, 'name');
    }

    /**
     * Summary of getAuthorSorts
     * @return array<string>
     */
    public function getAuthorSorts()
    {
        return array_column($this->authors, 'name');
    }

    /**
     * Set names for authors
     * @param array<string> $authorList
     * @return self
     */
    public function setAuthorNames($authorList)
    {
        foreach ($this->authors as $id => $author) {
            if (empty($author->id)
                || $author->id != $author->name
                || empty($authorList[$author->id])) {
                continue;
            }
            $author->name = $authorList[$author->id];
            $author->sort = AuthorInfo::getNameSort($author->name);
            $this->authors[$id] = $author;
        }
        return $this;
    }

    /**
     * Set titles for books & series in authors
     * @param array<string> $bookList
     * @param array<string> $seriesList
     * @return self
     */
    public function fixAuthors($bookList, $seriesList)
    {
        foreach ($this->authors as $id => $author) {
            $author->setBookTitles($bookList);
            $author->setSeriesTitles($seriesList);
            $this->authors[$id] = $author;
        }
        return $this;
    }
}
