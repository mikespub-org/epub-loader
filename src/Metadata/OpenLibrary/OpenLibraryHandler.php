<?php
/**
 * OpenLibraryHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\OpenLibrary;

use Marsender\EPubLoader\Handlers\MetadataHandler;
use Marsender\EPubLoader\RequestHandler;
use Exception;

class OpenLibraryHandler extends MetadataHandler
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
            case 'ol_author':
                if (!OpenLibraryMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $findLinks = $this->request->get('findLinks', false);
                $result = $this->ol_author($authorId, $matchId, $findLinks);
                break;
            case 'ol_books':
                $bookId = $this->request->getId('bookId');
                if (!OpenLibraryMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $result = $this->ol_books($authorId, $bookId, $matchId);
                break;
            case 'ol_work':
                $result = $this->ol_work($matchId);
                break;
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of ol_author
     * @param int|null $authorId
     * @param string|null $matchId
     * @param bool $findLinks
     * @return array<mixed>|null
     */
    public function ol_author($authorId, $matchId, $findLinks = false)
    {
        // Update the author link
        if (!is_null($authorId) && !is_null($matchId)) {
            $link = OpenLibraryMatch::link($matchId);
            if (!$this->db->setAuthorLink($authorId, $link)) {
                $this->addError($this->dbFileName, "Failed updating link {$link} for authorId {$authorId}");
                //return null;
            }
            //$authorId = null;
        }
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');

        // List the authors
        $authors = $this->db->getAuthors($authorId, $sort, $offset);
        $author = null;
        $query = null;
        if (!is_null($authorId) && is_null($matchId)) {
            $author = $authors[$authorId];
            $query = $author['name'];
        }

        // Find match on OpenLibrary
        $openlibrary = new OpenLibraryMatch($this->cacheDir);

        $matched = ['docs' => []];
        if (!empty($query)) {
            $matched = $openlibrary->findAuthors($query);
            usort($matched['docs'], function ($a, $b) {
                return $b['work_count'] <=> $a['work_count'];
            });
            // @todo Find works from author with highest work_count!?
            //if (count($matched) > 0) {
            //    $firstId = array_key_first($matched);
            //    $matched[$firstId]['entries'] = $openlibrary->findWorksByAuthor($author);
            //}
        } elseif (!empty($matchId)) {
            $matched['docs'][] = $openlibrary->getAuthor($matchId);
            //var_dump($matched);
        } elseif ($findLinks) {
            foreach ($authors as $id => $author) {
                if (empty($author['link'])) {
                    $matchId = $openlibrary->findAuthorId($author);
                    if (!empty($matchId)) {
                        $authors[$id]['link'] = OpenLibraryMatch::link($matchId);
                    }
                }
            }
        }
        $authors = $this->addAuthorInfo($authors, $authorId, $sort, $offset);
        $paging = $authorId ? null : $this->db->getAuthorPaging($sort, $offset);

        // Return info
        return [
            'authors' => $authors,
            'authorId' => $authorId,
            'matched' => $matched['docs'],
            'paging' => $paging,
        ];
    }

    /**
     * Summary of ol_books
     * @param int|null $authorId
     * @param int|null $bookId
     * @param string|null $matchId
     * @return array<mixed>|null
     */
    public function ol_books($authorId, $bookId, $matchId)
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
            $this->updateBookIdentifier('olid', $bookId, $matchId);
        }

        // Find match on OpenLibrary
        $openlibrary = new OpenLibraryMatch($this->cacheDir);

        // Get OpenLibrary author Id (if any)
        $authId = $this->request->get('authId');
        $matched = null;
        if (!empty($bookId)) {
            $books = $this->db->getBooks($bookId);
            $query = $books[$bookId]['title'];
            $matched = $openlibrary->findWorksByTitle($query, $author);
            // generic search returns 'docs' but author search returns 'entries'
            //$matched['entries'] ??= $matched['docs'];
        } elseif (!empty($authId)) {
            $sort = $this->request->get('sort');
            $offset = $this->request->getId('offset');
            $books = $this->db->getBooksByAuthor($authorId, $sort, $offset);
            $matched = $openlibrary->findWorksByAuthorId($authId);
        } else {
            $sort = $this->request->get('sort');
            $offset = $this->request->getId('offset');
            $books = $this->db->getBooksByAuthor($authorId, $sort, $offset);
            $authId = $openlibrary->findAuthorId($author);
            $matched = $openlibrary->findWorksByAuthorId($authId);
        }
        usort($matched['docs'], function ($a, $b) {
            return $b['edition_count'] <=> $a['edition_count'];
        });

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
        foreach ($matched['docs'] as $match) {
            if (array_key_exists($match['title'], $titles)) {
                if (!empty($match['author_name']) && in_array($author['name'], $match['author_name'])) {
                    $id = $titles[$match['title']];
                    $value = str_replace('/works/', '', $match['key']);
                    if (empty($books[$id]['identifiers']['olid']) || $books[$id]['identifiers']['olid']['value'] != $value) {
                        $books[$id]['identifiers']['ID:'] = ['id' => 0, 'book' => $id, 'type' => '* olid', 'value' => $value, 'url' => OpenLibraryMatch::link($value)];
                    } else {
                        $books[$id]['identifiers']['ID:'] = ['id' => 0, 'book' => $id, 'type' => '* olid', 'value' => '='];
                    }
                    unset($titles[$match['title']]);
                }
            }
        }

        // Return info
        return [
            'books' => $books,
            'authorId' => $authorId,
            'bookId' => $bookId,
            'matched' => $matched['docs'],
            'authors' => $authorList,
            'identifiers' => $identifierList,
            'identifierType' => 'olid',
        ];
    }

    /**
     * Summary of ol_work
     * @param string $workId
     * @return array<mixed>
     */
    public function ol_work($workId)
    {
        $work = [];

        // Get work on OpenLibrary
        if (!empty($workId)) {
            $openlibrary = new OpenLibraryMatch($this->cacheDir);
            $work = $openlibrary->getWork($workId);
        }

        // Return info
        return [
            'work' => $work,
            'workId' => $workId,
        ];
    }
}
