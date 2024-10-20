<?php
/**
 * WikiDataHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\WikiData;

use Marsender\EPubLoader\Handlers\MetadataHandler;
use Marsender\EPubLoader\RequestHandler;
use Exception;

class WikiDataHandler extends MetadataHandler
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
            case 'wd_author':
                if (!WikiDataMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $findLinks = $this->request->get('findLinks', false);
                $result = $this->wd_author($authorId, $matchId, $findLinks);
                break;
            case 'wd_books':
                $seriesId = $this->request->getId('seriesId');
                $bookId = $this->request->getId('bookId');
                if (!WikiDataMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $result = $this->wd_books($authorId, $seriesId, $bookId, $matchId);
                break;
            case 'wd_series':
                $seriesId = $this->request->getId('seriesId');
                if (!WikiDataMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $findLinks = $this->request->get('findLinks', false);
                $result = $this->wd_series($authorId, $seriesId, $matchId, $findLinks);
                break;
            case 'wd_entity':
                if (!WikiDataMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $seriesId = $this->request->getId('seriesId');
                $result = $this->wd_entity($matchId, $authorId, $seriesId);
                break;
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of wd_author
     * @param int|null $authorId
     * @param string|null $matchId
     * @param bool $findLinks
     * @return array<mixed>|null
     */
    public function wd_author($authorId, $matchId, $findLinks = false)
    {
        // Update the author link
        if (!is_null($authorId) && !is_null($matchId)) {
            $link = WikiDataMatch::link($matchId);
            if (!$this->db->setAuthorLink($authorId, $link)) {
                $this->addError($this->dbFileName, "Failed updating link {$link} for authorId {$authorId}");
                //return null;
            }
            $authorId = null;
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

        // Find match on Wikidata
        $wikimatch = new WikiDataMatch($this->cacheDir);

        $matched = null;
        if (!empty($query)) {
            $matched = $wikimatch->findAuthors($query);
            // Find works from author for 1st match
            if (count($matched) > 0) {
                $firstId = array_key_first($matched);
                $matched[$firstId]['entries'] = $wikimatch->findWorksByAuthorProperty($author);
            }
            // https://www.googleapis.com/books/v1/volumes?q=inauthor:%22Anne+Bishop%22&langRestrict=en&startIndex=0&maxResults=40
        } elseif ($findLinks) {
            foreach ($authors as $id => $author) {
                if (empty($author['link'])) {
                    $matchId = $wikimatch->findAuthorId($author);
                    if (!empty($matchId)) {
                        $authors[$id]['link'] = WikiDataMatch::link($matchId);
                    }
                }
            }
        }
        $authors = $this->addAuthorInfo($authors, $authorId, $sort, $offset);
        $paging = $authorId ? null : $this->db->getAuthorPaging($sort, $offset);

        // Return info
        return ['authors' => $authors, 'authorId' => $authorId, 'matched' => $matched, 'paging' => $paging];
    }

    /**
     * Summary of wd_books
     * @param int|null $authorId
     * @param int|null $seriesId
     * @param int|null $bookId
     * @param string|null $matchId
     * @return array<mixed>|null
     */
    public function wd_books($authorId, $seriesId, $bookId, $matchId)
    {
        if (!empty($bookId)) {
            $books = $this->db->getBooks($bookId);
            if (empty($authorId)) {
                $authorId = $books[$bookId]['author'];
            }
            if (empty($seriesId) && !empty($books[$bookId]['series'])) {
                $seriesId = $books[$bookId]['series'];
            }
        }
        if (!empty($seriesId)) {
            $series = $this->db->getSeries($seriesId);
            // series can have multiple authors
            $first = reset($series);
            if (empty($authorId)) {
                $authorId = $first['author'];
            }
        }
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
            if (!$this->updateBookIdentifier('wd', $bookId, $matchId)) {
                $this->addError($this->dbFileName, "Failed updating wd identifier for bookId {$bookId} to {$matchId}");
            }
        }

        // Find match on Wikidata
        $wikimatch = new WikiDataMatch($this->cacheDir);
        //$entityId = $wikimatch->findAuthorId($author);

        $matched = null;
        if (!empty($bookId)) {
            $books = $this->db->getBooks($bookId);
            /**
            if (!empty($entityId)) {
                // Find works from author
                $propId = 'P50';
                $results = $wikimatch->searchBy($propId, $entityId);
                $matched = $results->toArray();
            } else {
                $results = $wikimatch->search($books[0]['title']);
                $matched = $results->toArray();
            }
             */
            $query = $books[$bookId]['title'];
            $matched = $wikimatch->findWorksByTitle($query);
        } elseif (!empty($seriesId)) {
            $sort = $this->request->get('sort');
            $offset = $this->request->getId('offset');
            $books = $this->db->getBooksBySeries($seriesId, $sort, $offset);
            // @todo use author here too!?
            $matched = $wikimatch->findWorksByAuthorProperty($author);
            //$matched = array_merge($matched, $wikimatch->findWorksByAuthorName($author));
            if (empty($matched)) {
                $matched = $wikimatch->findWorksByAuthorName($author);
            }
        } else {
            $sort = $this->request->get('sort');
            $offset = $this->request->getId('offset');
            $books = $this->db->getBooksByAuthor($authorId, $sort, $offset);
            $matched = $wikimatch->findWorksByAuthorProperty($author);
            //$matched = array_merge($matched, $wikimatch->findWorksByAuthorName($author));
            if (empty($matched)) {
                $matched = $wikimatch->findWorksByAuthorName($author);
            }
        }

        $authorList = $this->getAuthorList();
        $titles = [];
        foreach ($books as $id => $book) {
            $titles[$book['title']] = $id;
        }
        // exact match only here - see calibre metadata plugins for more advanced features
        foreach ($matched as $match) {
            if (array_key_exists($match['label'], $titles)) {
                $id = $titles[$match['label']];
                $books[$id]['identifiers'][] = ['id' => 0, 'book' => $id, 'type' => '* wd', 'value' => $match['id'], 'url' => WikiDataMatch::link($match['id'])];
                unset($titles[$match['label']]);
            }
        }
        $seriesList = $this->getSeriesList($authorId);

        // Return info
        return ['books' => $books, 'authorId' => $authorId, 'seriesId' => $seriesId, 'bookId' => $bookId, 'matched' => $matched, 'authors' => $authorList, 'series' => $seriesList];
    }

    /**
     * Summary of wd_series
     * @param int|null $authorId
     * @param int|null $seriesId
     * @param string|null $matchId
     * @param bool $findLinks
     * @return array<mixed>|null
     */
    public function wd_series($authorId, $seriesId, $matchId, $findLinks = false)
    {
        if (empty($authorId) && empty($seriesId)) {
            //$this->addError($this->dbFileName, "Please specify authorId and/or seriesId");
            //return null;
            $authorList = $this->getAuthorList();
            $authorId = array_key_first($authorList);
        }
        $authors = $this->db->getAuthors($authorId);

        if (count($authors) < 1) {
            $this->addError($this->dbFileName, "Please specify a valid authorId");
            return null;
        }
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');

        // Find match on Wikidata
        $wikimatch = new WikiDataMatch($this->cacheDir);

        $matched = null;
        if (!empty($seriesId)) {
            $series = $this->db->getSeries($seriesId);
            // series can have multiple authors
            $first = reset($series);
            if (empty($authorId)) {
                $authorId = $first['author'];
            }
            // Update the series link
            if (!empty($matchId) && empty($first['link'])) {
                $link = WikiDataMatch::link($matchId);
                if (!$this->db->setSeriesLink($seriesId, $link)) {
                    $this->addError($this->dbFileName, "Failed updating link {$link} for seriesId {$seriesId}");
                    //return null;
                }
            } elseif (empty($matchId) && WikiDataMatch::isValidLink($first['link'])) {
                $matchId = WikiDataMatch::entity($first['link']);
            }
            $query = $first['name'];
            $matched = $wikimatch->findSeriesByName($query);
        } else {
            $series = $this->db->getSeriesByAuthor($authorId, $sort, $offset);
            if (count($series) > 0) {
                $author = $authors[$authorId];
                $matched = $wikimatch->findSeriesByAuthor($author);
            }
            if (empty($matchId) && !empty($findLinks)) {
                foreach ($series as $id => $serie) {
                    // @todo look up potential series by (cached) title / author(s)
                    if (empty($serie['link'])) {
                        //$found = $wikimatch->findSeriesByName($serie['name']);
                    }
                }
            }
        }
        $series = $this->addSeriesInfo($series, $seriesId, $sort, $offset);
        $paging = ($seriesId || $authorId) ? null : $this->db->getSeriesPaging($sort, $offset);

        $authorList = $this->getAuthorList();

        // Return info
        return ['series' => $series, 'authorId' => $authorId, 'seriesId' => $seriesId, 'matched' => $matched, 'authors' => $authorList, 'paging' => $paging];
    }

    /**
     * Summary of wd_entity
     * @param string|null $entityId
     * @param int|null $authorId
     * @param int|null $seriesId
     * @param string|null $query
     * @return array<mixed>
     */
    public function wd_entity($entityId = null, $authorId = null, $seriesId = null, $query = null)
    {
        $entity = [];
        // Get entity on Wikidata
        if (!empty($seriesId) && empty($entityId)) {
            $series = $this->db->getSeries($seriesId, $authorId);
            // series can have multiple authors
            $first = reset($series);
            if (empty($authorId)) {
                $authorId = $first['author'];
            }
            if (!empty($first['link']) && WikiDataMatch::isValidLink($first['link'])) {
                $entityId = WikiDataMatch::entity($first['link']);
            } else {
                $query = $first['name'];
                $wikimatch = new WikiDataMatch($this->cacheDir);
                //$matched = $wikimatch->findSeriesByName($query);
            }
        }
        if (!empty($authorId) && empty($entityId)) {
            $authors = $this->db->getAuthors($authorId);
            $author = $authors[$authorId];
            $wikimatch = new WikiDataMatch($this->cacheDir);
            $entityId = $wikimatch->findAuthorId($author);
        }
        if (!empty($entityId)) {
            $wikimatch = new WikiDataMatch($this->cacheDir);
            $entity = $wikimatch->getEntity($entityId);
        }
        $authorList = $this->getAuthorList();
        $seriesList = $this->getSeriesList($authorId);

        // Return info
        return ['entity' => $entity, 'entityId' => $entityId, 'authorId' => $authorId, 'seriesId' => $seriesId, 'authors' => $authorList, 'series' => $seriesList];
    }
}
