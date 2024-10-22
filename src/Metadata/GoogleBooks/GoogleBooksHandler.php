<?php
/**
 * GoogleBooksHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

use Marsender\EPubLoader\Handlers\MetadataHandler;
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
        $authors = $this->db->getAuthors($authorId);
        if (empty($authorId) && empty($bookId)) {
            //$this->addError($this->dbFileName, "Please specify authorId and/or bookId");
            //return null;
            $authorId = array_key_first($authors);
        }

        if (count($authors) < 1) {
            $this->addError($this->dbFileName, "Please specify a valid authorId");
            return null;
        }
        $author = $authors[$authorId];

        // Update the book identifier
        if (!is_null($bookId) && !is_null($matchId)) {
            $this->updateBookIdentifier('google', $bookId, $matchId);
        }

        // Find match on Google Books
        $googlematch = new GoogleBooksMatch($this->cacheDir, $lang);

        $matched = null;
        $dbPath = $this->dbConfig['db_path'];
        if (!empty($bookId)) {
            $books = $this->db->getBooks($bookId);
            $query = $books[$bookId]['title'];
            $matched = $googlematch->findWorksByTitle($query, $author);
            //$info = GoogleBooksMatch::getBookInfos($dbPath, $matched);
        } else {
            $sort = $this->request->get('sort');
            $offset = $this->request->getId('offset');
            $books = $this->db->getBooksByAuthor($authorId, $sort, $offset);
            $matched = $googlematch->findWorksByAuthor($author);
            //$info = GoogleBooksMatch::getBookInfos($dbPath, $matched);
        }

        $authorList = $this->getAuthorList();
        $titles = [];
        $identifierList = [];
        foreach ($books as $id => $book) {
            $titles[$book['title']] = $id;
            $diff = array_diff(array_keys($book['identifiers']), $identifierList);
            if (!empty($diff)) {
                $identifierList = array_merge($identifierList, $diff);
            }
        }
        $identifierList[] = 'ID:';
        sort($identifierList);
        // exact match only here - see calibre metadata plugins for more advanced features
        foreach ($matched['items'] as $match) {
            if (array_key_exists($match['volumeInfo']['title'], $titles)) {
                if (!empty($match['volumeInfo']['authors']) && in_array($author['name'], $match['volumeInfo']['authors'])) {
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
        $langList = GoogleBooksMatch::getLanguages();

        // Return info
        return [
            'books' => $books,
            'authorId' => $authorId,
            'bookId' => $bookId,
            'matched' => $matched,
            'authors' => $authorList,
            'lang' => $lang,
            'langList' => $langList,
            'identifiers' => $identifierList,
            'identifierType' => 'google',
        ];
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
