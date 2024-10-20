<?php
/**
 * MetadataHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Handlers;

use Marsender\EPubLoader\ActionHandler;
use Marsender\EPubLoader\RequestHandler;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsMatch;
use Marsender\EPubLoader\Metadata\GoogleBooks\GoogleBooksMatch;
use Marsender\EPubLoader\Metadata\OpenLibrary\OpenLibraryMatch;
use Marsender\EPubLoader\Metadata\WikiData\WikiDataMatch;

/**
 * @see Marsender\EPubLoader\Metadata\...\...Handler.php
 */
class MetadataHandler extends ActionHandler
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
        switch ($action) {
            case 'authors':
                $authorId = $this->request->getId('authorId');
                $sort = $this->request->get('sort');
                $result = $this->authors($authorId, $sort);
                break;
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of authors
     * @param int|null $authorId
     * @param string|null $sort
     * @return array<mixed>|null
     */
    public function authors($authorId = null, $sort = null)
    {
        $offset = $this->request->getId('offset');
        // List the authors
        $authors = $this->db->getAuthors($authorId, $sort, $offset);
        $matched = null;
        $authors = $this->addAuthorInfo($authors, $authorId, $sort, $offset);
        $paging = $authorId ? null : $this->db->getAuthorPaging($sort, $offset);

        // Return info
        return ['authors' => $authors, 'authorId' => $authorId, 'matched' => $matched, 'paging' => $paging];
    }

    /**
     * Summary of updateBookIdentifier
     * @param string $type
     * @param int $bookId
     * @param string $matchId
     * @return bool
     */
    public function updateBookIdentifier($type, $bookId, $matchId)
    {
        $books = $this->db->getBooks($bookId);
        $book = $books[$bookId];
        if (!empty($book) && !empty($book['identifiers'])) {
            foreach ($book['identifiers'] as $id => $identifier) {
                if ($identifier['type'] == $type) {
                    return $this->db->updateIdentifier($id, $matchId);
                }
            }
        }
        return $this->db->insertIdentifier($bookId, $type, $matchId);
    }

    /**
     * Summary of getAuthorList
     * @return array<mixed>
     */
    protected function getAuthorList()
    {
        // no limit for author names!?
        return $this->db->getAuthorNames();
    }

    /**
     * Summary of addAuthorInfo
     * @param array<mixed> $authors
     * @param int|null $authorId
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>
     */
    protected function addAuthorInfo($authors, $authorId = null, $sort = null, $offset = null)
    {
        $authors = $this->addBookCount($authors, $authorId);
        $authors = $this->addSeriesCount($authors, $authorId);
        // we order & slice here for books or series
        if (!empty($sort) && in_array($sort, ['books', 'series'])) {
            uasort($authors, function ($a, $b) use ($sort) {
                return $b[$sort] <=> $a[$sort];
            });
            $offset ??= 0;
            if (count($authors) > $this->db->limit) {
                $authors = array_slice($authors, $offset, $this->db->limit, true);
            }
        }
        $authors = $this->addAuthorLinks($authors);
        return $authors;
    }

    /**
     * Summary of addAuthorLinks
     * @param array<mixed> $authors
     * @return array<mixed>
     */
    protected function addAuthorLinks($authors)
    {
        foreach ($authors as $id => $author) {
            if (!empty($author['link'])) {
                if (WikiDataMatch::isValidLink($author['link'])) {
                    $authors[$id]['entityType'] = 'wd_entity';
                    $authors[$id]['entityId'] = WikiDataMatch::entity($author['link']);
                    continue;
                }
                if (OpenLibraryMatch::isValidLink($author['link'])) {
                    $authors[$id]['entityType'] = 'ol_work';
                    $authors[$id]['entityId'] = OpenLibraryMatch::entity($author['link']);
                    continue;
                }
                if (GoodReadsMatch::isValidLink($author['link'])) {
                    $authors[$id]['entityType'] = 'gr_author';
                    $authors[$id]['entityId'] = GoodReadsMatch::entity($author['link']);
                    continue;
                }
            }
        }
        return $authors;
    }

    /**
     * Summary of addBookCount
     * @param array<mixed> $authors
     * @param int|null $authorId
     * @return array<mixed>
     */
    protected function addBookCount($authors, $authorId = null)
    {
        $bookcount = $this->db->getBookCountByAuthor($authorId);
        foreach ($authors as $id => $author) {
            if (isset($bookcount[$id])) {
                $authors[$id]['books'] = $bookcount[$id];
            } else {
                $authors[$id]['books'] = '';
            }
        }
        return $authors;
    }

    /**
     * Summary of addSeriesCount
     * @param array<mixed> $authors
     * @param int|null $authorId
     * @return array<mixed>
     */
    protected function addSeriesCount($authors, $authorId = null)
    {
        $seriescount = $this->db->getSeriesCountByAuthor($authorId);
        foreach ($authors as $id => $author) {
            if (isset($seriescount[$id])) {
                $authors[$id]['series'] = $seriescount[$id];
            } else {
                $authors[$id]['series'] = '';
            }
        }
        return $authors;
    }

    /**
     * Summary of getSeriesList
     * @param int|null $authorId
     * @return array<mixed>
     */
    protected function getSeriesList($authorId = null)
    {
        // no limit for series titles!?
        return $this->db->getSeriesTitles($authorId);
    }

    /**
     * Summary of addSeriesInfo
     * @param array<mixed> $series
     * @param int|null $seriesId
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>
     */
    protected function addSeriesInfo($series, $seriesId = null, $sort = null, $offset = null)
    {
        $bookcount = [];
        foreach ($series as $id => $serie) {
            if (!isset($bookcount[$serie['id']])) {
                // keep key assoc here
                $bookcount = array_replace($bookcount, $this->db->getBookCountBySeries($serie['id']));
                $bookcount[$serie['id']] ??= '';
            }
            $series[$id]['books'] = $bookcount[$serie['id']];
            if (empty($serie['link'])) {
                continue;
            }
            // @todo fix overlap with addAuthorLinks()
            if (WikiDataMatch::isValidLink($serie['link'])) {
                $series[$id]['entityType'] = 'wd_entity';
                $series[$id]['entityId'] = WikiDataMatch::entity($serie['link']);
                continue;
            }
            if (str_starts_with($serie['link'], GoodReadsMatch::SERIES_URL)) {
                $series[$id]['entityType'] = 'gr_series';
                $series[$id]['entityId'] = str_replace(GoodReadsMatch::SERIES_URL, '', $serie['link']);
                continue;
            }
        }
        // we order & slice here for books
        if (!empty($sort) && in_array($sort, ['books'])) {
            uasort($series, function ($a, $b) use ($sort) {
                return $b[$sort] <=> $a[$sort];
            });
            $offset ??= 0;
            if (count($series) > $this->db->limit) {
                $series = array_slice($series, $offset, $this->db->limit, true);
            }
        }
        return $series;
    }
}
