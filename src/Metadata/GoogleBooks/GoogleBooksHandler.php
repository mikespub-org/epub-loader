<?php

/**
 * GoogleBooksHandler class
 */

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

use Marsender\EPubLoader\Handlers\MetadataHandler;
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\RequestHandler;
use Exception;

class GoogleBooksHandler extends MetadataHandler
{
    /**
     * Summary of handle
     * @param string $action
     * @param RequestHandler $request
     * @return mixed
     */
    public function handle($action, $request)
    {
        $this->request = $request;
        $authorId = $this->request->getId('authorId');
        $matchId = $this->request->get('matchId');
        switch ($action) {
            case 'gb_books':
                $bookId = $this->request->getId('bookId');
                $lang = $this->request->get('lang', 'en');
                $result = $this->gb_books($authorId, $bookId, $matchId, $lang);
                break;
            case 'gb_volume':
                $lang = $this->request->get('lang', 'en');
                $result = $this->gb_volume($matchId, $lang);
                break;
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of gb_books
     * @param int|null $authorId
     * @param int|null $bookId
     * @param string|null $matchId
     * @param string $lang
     * @return array<mixed>|null
     */
    public function gb_books($authorId, $bookId, $matchId, $lang = 'en')
    {
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');
        $result = $this->books($authorId, null, $bookId, $sort, $offset);
        $authorId = $result['authorId'];
        /** @var AuthorInfo|null $authorInfo */
        $authorInfo = $result['authorInfo'];
        /** @var BookInfo|null $bookInfo */
        $bookInfo = $result['bookInfo'];

        // Update the book identifier
        if (!is_null($bookId) && !is_null($matchId)) {
            $this->updateBookIdentifier('google', $bookId, $matchId);
        }

        // Find match on Google Books
        $googlematch = new GoogleBooksMatch($this->cacheDir, $lang);

        $matched = null;
        $dbPath = $this->dbConfig['db_path'];
        if (!empty($bookId) && !empty($bookInfo)) {
            $matched = $googlematch->findWorksByTitle($bookInfo->title, $authorInfo->name);
            //$info = GoogleBooksMatch::getBookInfo($dbPath, $matched);
        } else {
            $matched = $googlematch->findWorksByAuthor($authorInfo->name);
            //$info = GoogleBooksMatch::getBookInfo($dbPath, $matched);
        }

        // exact match only here - see calibre metadata plugins for more advanced features
        if (!empty($authorId) && !empty($authorInfo)) {
            $result['books'] = $this->matchBookTitles($result['books'], $matched, $authorInfo->name);
        }

        $result['matched'] = $matched;
        $result['matchId'] = $matchId;
        $result['lang'] = $lang;
        $result['langList'] = GoogleBooksMatch::getLanguages();
        $result['identifierType'] = 'google';

        // Return info
        return $result;
    }

    /**
     * Summary of matchBookTitles
     * @param array<mixed> $books
     * @param array<mixed> $matched
     * @param string $authorName
     * @return array<mixed>
     */
    protected function matchBookTitles($books, $matched, $authorName)
    {
        // exact match only here - see calibre metadata plugins for more advanced features
        $titles = [];
        foreach ($books as $id => $book) {
            $titles[$book['title']] = $id;
        }
        foreach ($matched['items'] as $match) {
            if (array_key_exists($match['volumeInfo']['title'], $titles)) {
                if (!empty($match['volumeInfo']['authors']) && in_array($authorName, $match['volumeInfo']['authors'])) {
                    $id = $titles[$match['volumeInfo']['title']];
                    if (empty($books[$id]['identifiers']['google']) || $books[$id]['identifiers']['google']['value'] != $match['id']) {
                        $books[$id]['identifiers']['ID:'] = ['id' => 0, 'book' => $id, 'type' => '* google', 'value' => $match['id'], 'url' => GoogleBooksMatch::link($match['id'])];
                    } else {
                        $books[$id]['identifiers']['ID:'] = ['id' => 0, 'book' => $id, 'type' => '* google', 'value' => '='];
                    }
                    unset($titles[$match['volumeInfo']['title']]);
                }
            }
        }
        return $books;
    }

    /**
     * Summary of gb_volume
     * @param string $volumeId
     * @param string $lang
     * @return array<mixed>
     */
    public function gb_volume($volumeId, $lang)
    {
        $volume = [];

        // Get volume on Google Books
        if (!empty($volumeId)) {
            $googlematch = new GoogleBooksMatch($this->cacheDir, $lang);
            $volume = $googlematch->getVolume($volumeId);
        }
        $langList = GoogleBooksMatch::getLanguages();

        // Return info
        return [
            'volume' => $volume,
            'volumeId' => $volumeId,
            'lang' => $lang,
            'langList' => $langList,
        ];
    }
}
