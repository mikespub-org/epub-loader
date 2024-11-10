<?php
/**
 * OpenLibraryHandler class
 */

namespace Marsender\EPubLoader\Metadata\OpenLibrary;

use Marsender\EPubLoader\Handlers\MetadataHandler;
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
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
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');
        $result = $this->authors($authorId, $sort, $offset);
        $authorId = $result['authorId'];
        /** @var AuthorInfo|null $authorInfo */
        $authorInfo = $result['authorInfo'];

        // Find matchId from author link
        if (empty($matchId) && !empty($authorInfo) && OpenLibraryMatch::isValidLink($authorInfo->link)) {
            $matchId = OpenLibraryMatch::entity($authorInfo->link);
        }

        // Find match on OpenLibrary
        $openlibrary = new OpenLibraryMatch($this->cacheDir);

        if (empty($matchId) && !empty($findLinks)) {
            $result['authors'] = $this->findAuthorLinks($openlibrary, $result['authors']);
        }
        $matched = ['docs' => []];
        if (!empty($authorId) && !empty($authorInfo)) {
            $name = $authorInfo->name;
            $matched = $this->getAuthorsByName($openlibrary, $name);
        } elseif (!empty($matchId)) {
            $matched['docs'][] = $openlibrary->getAuthor($matchId);
            // Update the author link
            if (!is_null($authorId) && empty($authorInfo->link)) {
                $link = OpenLibraryMatch::link($matchId);
                if (!$this->db->setAuthorLink($authorId, $link)) {
                    $this->addError($this->dbFileName, "Failed updating link {$link} for authorId {$authorId}");
                }
                // @todo $result['authors'][$authorId]['link'] = $link;
            }
        }

        // different format for $matched here
        $result['matched'] = $matched['docs'];
        $result['matchId'] = $matchId;

        // Return info
        return $result;
    }

    /**
     * Summary of findAuthorLinks
     * @param OpenLibraryMatch $match
     * @param array<mixed> $authors
     * @return array<mixed>
     */
    protected function findAuthorLinks($match, $authors)
    {
        foreach ($authors as $id => $authorInfo) {
            /** @var array<mixed> $authorInfo */
            if (empty($authorInfo['link'])) {
                $matchId = $match->findAuthorId($authorInfo);
                if (!empty($matchId)) {
                    $authors[$id]['link'] = OpenLibraryMatch::link($matchId);
                }
            }
        }
        // we need to run this again here...
        $authors = $this->addAuthorLinks($authors);
        return $authors;
    }

    /**
     * Summary of getAuthorsByName
     * @param OpenLibraryMatch $match
     * @param string $name
     * @return array<mixed>
     */
    protected function getAuthorsByName($match, $name)
    {
        $matched = $match->findAuthors($name);
        usort($matched['docs'], function ($a, $b) {
            return $b['work_count'] <=> $a['work_count'];
        });
        // @todo Find works from author with highest work_count!?
        //if (count($matched) > 0) {
        //    $firstId = array_key_first($matched);
        //    $matched[$firstId]['entries'] = $openlibrary->findWorksByAuthor($authorInfo);
        //}
        return $matched;
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
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');
        $result = $this->books($authorId, null, $bookId, $sort, $offset);
        $authorId = $result['authorId'];
        /** @var AuthorInfo|null $authorInfo */
        $authorInfo = $result['authorInfo'];
        /** @var BookInfo|null $bookInfo */
        $bookInfo = $result['bookInfo'];

        // Get OpenLibrary author Id (if any)
        $authId = $this->request->get('authId');

        // Find authId from author link
        if (empty($authId) && !empty($authorInfo) && OpenLibraryMatch::isValidLink($authorInfo->link)) {
            $authId = OpenLibraryMatch::entity($authorInfo->link);
        }

        // Find match on OpenLibrary
        $openlibrary = new OpenLibraryMatch($this->cacheDir);

        $matched = null;
        if (!empty($bookId) && !empty($bookInfo)) {
            $matched = $openlibrary->findWorksByTitle($bookInfo->title, $authorInfo->name);
            // generic search returns 'docs' but author search returns 'entries'
            //$matched['entries'] ??= $matched['docs'];
            // Update the book identifier
            if (!is_null($matchId) && empty($authorInfo->link)) {
                $this->updateBookIdentifier('olid', $bookId, $matchId);
            }
        } elseif (!empty($authId)) {
            $matched = $openlibrary->findWorksByAuthorId($authId);
        } else {
            $authId = $openlibrary->findAuthorId($authorInfo);
            $matched = $openlibrary->findWorksByAuthorId($authId);
        }
        usort($matched['docs'], function ($a, $b) {
            return $b['edition_count'] <=> $a['edition_count'];
        });

        // exact match only here - see calibre metadata plugins for more advanced features
        if (!empty($authorId) && !empty($authorInfo)) {
            $result['books'] = $this->matchBookTitles($result['books'], $matched, $authorInfo->name);
        }

        // different format for $matched here
        $result['matched'] = $matched['docs'];
        $result['matchId'] = $matchId;
        $result['authId'] = $authId;
        $result['identifierType'] = 'olid';

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
        foreach ($matched['docs'] as $match) {
            if (array_key_exists($match['title'], $titles)) {
                if (!empty($match['author_name']) && in_array($authorName, $match['author_name'])) {
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
        return $books;
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
