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
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
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
        $authorId = $this->request->getId('authorId');
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');
        switch ($action) {
            case 'authors':
                $result = $this->authors($authorId, $sort);
                break;
            case 'series':
                $seriesId = $this->request->getId('seriesId');
                $result = $this->series($authorId, $seriesId, $sort, $offset);
                break;
            case 'books':
                $seriesId = $this->request->getId('seriesId');
                $bookId = $this->request->getId('bookId');
                $result = $this->books($authorId, $seriesId, $bookId, $sort, $offset);
                break;
            default:
                $result = $this->$action();
        }
        if (!empty($result) && is_array($result)) {
            $result['raw'] = $this->request->get('raw');
        }
        return $result;
    }

    /**
     * Summary of authors
     * @param int|null $authorId
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>|null
     */
    public function authors($authorId = null, $sort = null, $offset = null)
    {
        // Get matchId from template

        // List the authors
        $authors = $this->db->getAuthors($authorId, $sort, $offset);

        // Find match on ...
        $matched = null;

        // Find matchId from author link
        $authorInfo = null;
        if (!empty($authorId)) {
            $authorInfo = $authors[$authorId];
        }

        // Find author links if requested
        $authors = $this->addAuthorInfo($authors, $authorId, $sort, $offset);
        $paging = $authorId ? null : $this->db->getAuthorPaging($sort, $offset);

        $dbPath = $this->dbConfig['db_path'];
        // Return info
        return [
            'authors' => $authors,
            'authorId' => $authorId,
            'authorInfo' => AuthorInfo::load($dbPath, $authorInfo, $this->db),
            'matched' => $matched,
            'paging' => $paging,
        ];
    }

    /**
     * Summary of books
     * @param int|null $authorId
     * @param int|null $seriesId
     * @param int|null $bookId
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>|null
     */
    public function books($authorId, $seriesId, $bookId, $sort = null, $offset = null)
    {
        // Get matchId from template
        // Get authId from template
        // Get serId from template
        $bookInfo = null;
        if (!empty($bookId)) {
            $books = $this->db->getBooks($bookId);
            if (empty($authorId)) {
                $authorId = $books[$bookId]['author'];
            }
            if (empty($seriesId) && !empty($books[$bookId]['series'])) {
                $seriesId = $books[$bookId]['series'];
            }
            $bookInfo = $books[$bookId];
        }
        // Find serId from series link
        $seriesInfo = null;
        if (!empty($seriesId)) {
            $series = $this->db->getSeries($seriesId);
            // series can have multiple authors - pick the first
            $seriesInfo = reset($series);
            if (empty($authorId)) {
                $authorId = $seriesInfo['author'];
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
        // Find authId from author link
        $authorInfo = $authors[$authorId];

        // Find match on ...
        $matched = null;

        if (!empty($bookId)) {
            $books = $this->db->getBooks($bookId);
        } elseif (!empty($seriesId)) {
            $books = $this->db->getBooksBySeries($seriesId, $sort, $offset);
        } else {
            $books = $this->db->getBooksByAuthor($authorId, $sort, $offset);
        }

        $authorList = $this->getAuthorList();
        $identifierList = [];
        foreach ($books as $id => $book) {
            $diff = array_diff(array_keys($book['identifiers']), $identifierList);
            if (!empty($diff)) {
                $identifierList = array_merge($identifierList, $diff);
            }
        }
        $identifierList[] = 'ID:';
        sort($identifierList);
        $seriesList = $this->getSeriesList($authorId);

        $dbPath = $this->dbConfig['db_path'];
        // Return info
        return [
            'books' => $books,
            'authorId' => $authorId,
            'authorInfo' => AuthorInfo::load($dbPath, $authorInfo),
            'seriesId' => $seriesId,
            'seriesInfo' => SeriesInfo::load($dbPath, $seriesInfo),
            'bookId' => $bookId,
            'bookInfo' => BookInfo::load($dbPath, $bookInfo, $this->db),
            'matched' => $matched,
            'authors' => $authorList,
            'series' => $seriesList,
            'identifiers' => $identifierList,
            //'identifierType' => '',
            //'matchId' => $matchId,
            //'serId' => $serId,
        ];
    }

    /**
     * Summary of series
     * @param int|null $authorId
     * @param int|null $seriesId
     * @param string|null $sort
     * @param int|null $offset
     * @return array<mixed>|null
     */
    public function series($authorId, $seriesId, $sort = null, $offset = null)
    {
        // Get matchId from template

        // Find match on ...
        $matched = null;

        $seriesInfo = null;
        if (!empty($seriesId)) {
            $series = $this->db->getSeries($seriesId, $authorId, null, $sort, $offset);
            // series can have multiple authors - pick the first
            $seriesInfo = reset($series);
            if (empty($authorId)) {
                $authorId = $seriesInfo['author'];
            }
        } else {
            $series = $this->db->getSeriesByAuthor($authorId, $sort, $offset);
            // Find series links if requested
        }
        // Find authId from author link
        $authorInfo = null;
        if (!empty($authorId)) {
            $authors = $this->db->getAuthors($authorId);
            $authorInfo = $authors[$authorId];
        }
        $series = $this->addSeriesInfo($series, $seriesId, $sort, $offset);
        $paging = ($seriesId || $authorId) ? null : $this->db->getSeriesPaging($sort, $offset);

        $authorList = $this->getAuthorList();

        $dbPath = $this->dbConfig['db_path'];
        // Return info
        return [
            'series' => $series,
            'authorId' => $authorId,
            'authorInfo' => AuthorInfo::load($dbPath, $authorInfo),
            'seriesId' => $seriesId,
            'seriesInfo' => SeriesInfo::load($dbPath, $seriesInfo, $this->db),
            'matched' => $matched,
            'authors' => $authorList,
            'paging' => $paging,
        ];
    }

    /**
     * Summary of updateBookIdentifier
     * @param string $type
     * @param int $bookId
     * @param string $matchId
     * @return bool
     */
    protected function updateBookIdentifier($type, $bookId, $matchId)
    {
        $books = $this->db->getBooks($bookId);
        $book = $books[$bookId];
        if (!empty($book) && !empty($book['identifiers'])) {
            if (!empty($book['identifiers'][$type])) {
                $id = $book['identifiers'][$type]['id'];
                return $this->db->updateIdentifier($id, $matchId);
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
        return AuthorInfo::getNameList($this->db);
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
        if (empty($authorId)) {
            return SeriesInfo::getTitleList($this->db);
        }
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
        $series = $this->addSeriesLinks($series);
        return $series;
    }

    /**
     * Summary of addSeriesLinks
     * @param array<mixed> $series
     * @return array<mixed>
     */
    protected function addSeriesLinks($series)
    {
        foreach ($series as $id => $serie) {
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
        return $series;
    }
}
