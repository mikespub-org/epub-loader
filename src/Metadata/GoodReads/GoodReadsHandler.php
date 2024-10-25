<?php
/**
 * GoodReadsHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\GoodReads;

use Marsender\EPubLoader\Handlers\MetadataHandler;
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Marsender\EPubLoader\RequestHandler;
use Exception;

class GoodReadsHandler extends MetadataHandler
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
            case 'gr_author':
                $findLinks = $this->request->get('findLinks', false);
                $result = $this->gr_author($authorId, $matchId, $findLinks);
                break;
            case 'gr_series':
                $seriesId = $this->request->getId('seriesId');
                $findLinks = $this->request->get('findLinks', false);
                $result = $this->gr_series($authorId, $seriesId, $matchId, $findLinks);
                break;
            case 'gr_books':
                $seriesId = $this->request->getId('seriesId');
                $bookId = $this->request->getId('bookId');
                $result = $this->gr_books($authorId, $seriesId, $bookId, $matchId);
                break;
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of gr_author
     * @param int|null $authorId
     * @param string|null $matchId
     * @param bool $findLinks
     * @return array<mixed>|null
     */
    public function gr_author($authorId, $matchId, $findLinks = false)
    {
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');
        $result = $this->authors($authorId, $sort, $offset);
        $authorId = $result['authorId'];
        /** @var AuthorInfo|null $authorInfo */
        $authorInfo = $result['authorInfo'];

        // Find matchId from author link
        if (empty($matchId) && !empty($authorInfo) && GoodReadsMatch::isValidLink($authorInfo->link)) {
            $matchId = GoodReadsMatch::entity($authorInfo->link);
        }

        // Find match on GoodReads
        $goodreads = new GoodReadsMatch($this->cacheDir);

        if (empty($matchId) && !empty($findLinks)) {
            $result['authors'] = $this->findAuthorLinks($goodreads, $result['authors']);
        }
        $matched = [];
        if (!empty($matchId)) {
            $matched = $this->getAuthorsMatched($goodreads, $matchId, $authorId, $authorInfo);
        } elseif (!empty($authorId) && !empty($authorInfo)) {
            $name = $authorInfo->name;
            $matched = $this->getAuthorsByName($goodreads, $name);
        } elseif (empty($authorId)) {
            // @todo show all availables authors if no author is selected?
            $matched = $this->getAuthorsCached($goodreads, $offset);
        }
        // Get bookid for books
        foreach ($matched as $key => $match) {
            foreach ($match['books'] as $id => $book) {
                $matched[$key]['books'][$id]['key'] = $book['id'];
                $matched[$key]['books'][$id]['id'] = GoodReadsMatch::bookid($book['id']);
            }
        }
        $result['matched'] = $matched;
        $result['matchId'] = $matchId;

        // Return info
        return $result;
    }

    /**
     * Summary of findAuthorLinks
     * @param GoodReadsMatch $match
     * @param array<mixed> $authors
     * @return array<mixed>
     */
    protected function findAuthorLinks($match, $authors)
    {
        foreach ($authors as $id => $authorInfo) {
            if (empty($authorInfo['link'])) {
                $matchId = $match->findAuthorId($authorInfo);
                if (!empty($matchId)) {
                    $authors[$id]['link'] = GoodReadsMatch::AUTHOR_URL . $matchId;
                }
            }
        }
        // we need to run this again here...
        $authors = $this->addAuthorLinks($authors);
        return $authors;
    }

    /**
     * Summary of getAuthorsMatched
     * @param GoodReadsMatch $match
     * @param string|null $matchId
     * @param int|null $authorId
     * @param AuthorInfo|null $authorInfo
     * @return array<mixed>
     */
    protected function getAuthorsMatched($match, $matchId, $authorId, $authorInfo)
    {
        $matched = [];
        // remove other authors here?
        $found = $match->getAuthor($matchId);
        if (empty($found[$matchId])) {
            throw new Exception('Unable to find matching author');
        }
        $matched[$matchId] = $found[$matchId];
        // Update the author link
        if (!is_null($authorId) && empty($authorInfo->link)) {
            $link = GoodReadsMatch::AUTHOR_URL . $matchId;
            if (!$this->db->setAuthorLink($authorId, $link)) {
                $this->addError($this->dbFileName, "Failed updating link {$link} for authorId {$authorId}");
            }
            // @todo $result['authors'][$authorId]->link = $link;
        }
        return $matched;
    }

    /**
     * Summary of getAuthorsByName
     * @param GoodReadsMatch $match
     * @param string $name
     * @return array<mixed>
     */
    protected function getAuthorsByName($match, $name)
    {
        $matched = $match->findAuthors($name);
        // @todo Find author with highest books count!?
        uasort($matched, function ($a, $b) {
            return count($b['books']) <=> count($a['books']);
        });
        return $matched;
    }

    /**
     * Summary of getAuthorsCached
     * @param GoodReadsMatch $match
     * @param int|null $offset
     * @return array<mixed>
     */
    protected function getAuthorsCached($match, $offset)
    {
        $matched = [];
        // @todo show all availables authors if no author is selected?
        foreach ($match->getCachedAuthorNames() as $id => $name) {
            $matched[$id] = [
                'id' => $id,
                'name' => $name,
                'books' => [],
            ];
        }
        $offset ??= 0;
        if (count($matched) > $this->db->limit) {
            $matched = array_slice($matched, $offset, $this->db->limit);
        }
        return $matched;
    }

    /**
     * Summary of gr_books
     * @param int|null $authorId
     * @param int|null $seriesId
     * @param int|null $bookId
     * @param string|null $matchId
     * @return array<mixed>|null
     */
    public function gr_books($authorId, $seriesId, $bookId, $matchId)
    {
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');
        $result = $this->books($authorId, $seriesId, $bookId, $sort, $offset);
        $authorId = $result['authorId'];
        /** @var AuthorInfo|null $authorInfo */
        $authorInfo = $result['authorInfo'];
        $seriesId = $result['seriesId'];
        $seriesInfo = $result['seriesInfo'];
        /** @var BookInfo|null|null|null $bookInfo */
        $bookInfo = $result['bookInfo'];

        // Get GoodReads author Id from gr_author.html (if any)
        $authId = $this->request->get('authId');
        // Get GoodReads series Id from gr_series.html (if any)
        $serId = $this->request->get('serId');

        // Find serId from series link
        if (empty($serId) && !empty($seriesInfo) && GoodReadsMatch::isValidLink($seriesInfo->link)) {
            $serId = GoodReadsMatch::entity($seriesInfo->link);
        }
        // Find authId from author link
        if (empty($authId) && !empty($authorInfo) && GoodReadsMatch::isValidLink($authorInfo->link)) {
            $authId = GoodReadsMatch::entity($authorInfo->link);
        }

        // Find match on GoodReads
        $goodreads = new GoodReadsMatch($this->cacheDir);

        $matched = [];
        if (!empty($matchId)) {
            $matched = $this->getBooksMatched($goodreads, $matchId, $bookId);
        } elseif (!empty($authId)) {
            $matched = $this->getBooksByAuthor($goodreads, $authId);
        } elseif (!empty($serId)) {
            $matched = $this->getBooksBySeries($goodreads, $serId);
        } elseif (!empty($authorId) && !empty($authorInfo)) {
            $authId = $goodreads->findAuthorId($authorInfo);
            $matched = $this->getBooksByAuthor($goodreads, $authId);
        }
        // Get bookid for books
        foreach ($matched as $key => $match) {
            $matched[$key]['key'] = $match['id'];
            $matched[$key]['id'] = GoodReadsMatch::bookid($match['id']);
        }

        // exact match only here - see calibre metadata plugins for more advanced features
        $result['books'] = $this->matchBookTitles($result['books'], $matched);

        $result['matched'] = $matched;
        $result['matchId'] = $matchId;
        $result['authId'] = $authId;
        $result['serId'] = $serId;
        $result['identifierType'] = 'goodreads';

        // Return info
        return $result;
    }

    /**
     * Summary of getBooksMatched
     * @param GoodReadsMatch $match
     * @param string $matchId
     * @param int|null $bookId
     * @return array<mixed>
     */
    protected function getBooksMatched($match, $matchId, $bookId)
    {
        $matched = [];
        $found = $match->getBook($matchId);
        $dbPath = $this->dbConfig['db_path'];
        $bookResult = GoodReadsCache::parseBook($found);
        $info = GoodReadsImport::load($dbPath, $bookResult);
        $seriesInfo = $info->getSeriesInfo();
        $matched[] = [
            'id' => GoodReadsMatch::entity($info->uri),
            'title' => $info->title,
            'url' => $info->uri,
            'cover' => $info->cover,
            'series' => [
                'id' => $seriesInfo->id,
                'title' => $seriesInfo->title,
                'index' => $seriesInfo->index,
            ],
        ];
        // Update the book identifier
        if (!is_null($bookId)) {
            $this->updateBookIdentifier('goodreads', $bookId, $matchId);
            // @todo $result['books'][$bookId]['identifiers']['goodreads'] = $matchId;
        }
        return $matched;
    }

    /**
     * Summary of getBooksByAuthor
     * @param GoodReadsMatch $match
     * @param string $authId
     * @return array<mixed>
     */
    protected function getBooksByAuthor($match, $authId)
    {
        $matched = [];
        $found = $match->getAuthor($authId);
        // remove books from other authors here?
        if (!empty($found[$authId])) {
            $matched = $found[$authId]['books'];
        }
        return $matched;
    }

    /**
     * Summary of getBooksBySeries
     * @param GoodReadsMatch $match
     * @param string $serId
     * @return array<mixed>
     */
    protected function getBooksBySeries($match, $serId)
    {
        $matched = [];
        $found = $match->getSeries($serId);
        $info = GoodReadsCache::parseSeries($found);
        // id is not available in JSON data - this must be set by caller
        $info->setId($serId);
        foreach ($info->getBookList() as $book) {
            $matched[] = [
                'id' => $book->getBookId(),
                'title' => $book->getTitle(),
                'bare' => $book->getBookTitleBare(),
                'header' => $book->getSeriesHeader(),
                'url' => $book->getBookUrl(),
                'cover' => $book->getImageUrl(),
                'series' => [
                    'id' => $info->getId(),
                    'title' => $info->getTitle(),
                    'index' => $book->getSeriesHeader(),
                ],
            ];
        }
        return $matched;
    }

    /**
     * Summary of matchBookTitles
     * @param array<mixed> $books
     * @param array<mixed> $matched
     * @return array<mixed>
     */
    protected function matchBookTitles($books, $matched)
    {
        // exact match only here - see calibre metadata plugins for more advanced features
        $titles = [];
        foreach ($books as $id => $book) {
            $titles[$book['title']] = $id;
        }
        foreach ($matched as $key => $match) {
            if (array_key_exists($match['title'], $titles)) {
                $id = $titles[$match['title']];
                $book = $books[$id];
                if (empty($book['identifiers']['goodreads']) || $book['identifiers']['goodreads']['value'] != $matched[$key]['id']) {
                    $books[$id]['identifiers']['ID:'] = ['id' => 0, 'book' => $id, 'type' => '* goodreads', 'value' => $matched[$key]['id'], 'url' => GoodReadsMatch::link($match['id'])];
                } else {
                    $books[$id]['identifiers']['ID:'] = ['id' => 0, 'book' => $id, 'type' => '* goodreads', 'value' => '='];
                }
                unset($titles[$match['title']]);
            }
        }
        return $books;
    }

    /**
     * Summary of gr_series
     * @param int|null $authorId
     * @param int|null $seriesId
     * @param string|null $matchId
     * @param bool $findLinks
     * @return array<mixed>|null
     */
    public function gr_series($authorId, $seriesId, $matchId, $findLinks = false)
    {
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');
        $result = $this->series($authorId, $seriesId, $sort, $offset);
        // series can have multiple authors
        $authorId = $result['authorId'];
        ///** @var AuthorInfo|null $authorInfo */
        //$authorInfo = $result['authorInfo'];
        $seriesId = $result['seriesId'];
        /** @var SeriesInfo|null $seriesInfo */
        $seriesInfo = $result['seriesInfo'];

        // Find matchId from series link
        if (empty($matchId) && !empty($seriesInfo) && GoodReadsMatch::isValidLink($seriesInfo->link)) {
            $matchId = GoodReadsMatch::entity($seriesInfo->link);
        }

        // Find match on GoodReads
        $goodreads = new GoodReadsMatch($this->cacheDir);

        if (empty($matchId) && !empty($findLinks)) {
            $result['series'] = $this->findSeriesLinks($goodreads, $result['series']);
        }
        $matched = null;
        if (!empty($matchId)) {
            $matched = $this->getSeriesMatched($goodreads, $matchId, $seriesId, $seriesInfo);
        } elseif (!empty($seriesId) && !empty($seriesInfo)) {
            $title = $seriesInfo->title;
            $matched = $this->getSeriesByTitle($goodreads, $title);
        } elseif (empty($authorId)) {
            // @todo show all availables series if no author is selected?
            $matched = $this->getSeriesCached($goodreads, $offset);
        }
        // Get bookid for books

        $result['matched'] = $matched;
        $result['matchId'] = $matchId;

        // Return info
        return $result;
    }

    /**
     * Summary of findSeriesLinks
     * @param GoodReadsMatch $match
     * @param array<mixed> $series
     * @return array<mixed>
     */
    protected function findSeriesLinks($match, $series)
    {
        foreach ($series as $id => $serie) {
            // @todo look up potential series by (cached) title / author(s)
            if (empty($serie['link'])) {
                $matchId = $match->findCachedSeriesId($serie['name']);
                if (!empty($matchId)) {
                    $series[$id]['link'] = GoodReadsMatch::SERIES_URL . $matchId;
                }
            }
        }
        // we need to run this again here...
        $series = $this->addSeriesLinks($series);
        return $series;
    }

    /**
     * Summary of getSeriesMatched
     * @param GoodReadsMatch $match
     * @param string $matchId
     * @param int|null $seriesId
     * @param SeriesInfo|null $seriesInfo
     * @return array<mixed>
     */
    protected function getSeriesMatched($match, $matchId, $seriesId, $seriesInfo)
    {
        $matched = [];
        $found = $match->getSeries($matchId);
        if (empty($found)) {
            return $matched;
        }
        $info = GoodReadsCache::parseSeries($found);
        $info->setId($matchId);
        // Update the series link
        if (!empty($seriesId) && empty($seriesInfo->link)) {
            $link = GoodReadsMatch::SERIES_URL . $matchId;
            if (!$this->db->setSeriesLink($seriesId, $link)) {
                $this->addError($this->dbFileName, "Failed updating link {$link} for seriesId {$seriesId}");
            }
            // @todo $result['series'][$seriesId]['link'] = $link;
        }
        return $this->parseSeriesResult($info);
    }

    /**
     * Summary of getSeriesByTitle
     * @param GoodReadsMatch $match
     * @param string $title
     * @return array<mixed>
     */
    protected function getSeriesByTitle($match, $title)
    {
        $matched = [];
        $found = $match->findSeriesByTitle($title);
        if (empty($found)) {
            return $matched;
        }
        // set in GoodReadsMatch::findSeriesByTitle()
        $matchId = $found[0][1]['id'];
        $info = GoodReadsCache::parseSeries($found);
        $info->setId($matchId);
        return $this->parseSeriesResult($info);
    }

    /**
     * Summary of parseSeriesResult
     * @param Series\SeriesResult $info
     * @return array<mixed>
     */
    protected function parseSeriesResult($info)
    {
        $matched = [];
        $match = [
            'id' => $info->getId(),
            'title' => $info->getTitle(),
            'count' => $info->getNumWorks(),
            'description' => $info->getDescription(),
            'link' => 'https://www.goodreads.com/series/' . $info->getId(),
            'books' => [],
        ];
        foreach ($info->getBookList() as $book) {
            $match['books'][] = [
                'id' => $book->getBookId(),
                'title' => $book->getTitle(),
                'bare' => $book->getBookTitleBare(),
                'header' => $book->getSeriesHeader(),
                'url' => $book->getBookUrl(),
                'cover' => $book->getImageUrl(),
            ];
        }
        $matched[] = $match;
        return $matched;
    }

    /**
     * Summary of getSeriesCached
     * @param GoodReadsMatch $match
     * @param int|null $offset
     * @return array<mixed>
     */
    protected function getSeriesCached($match, $offset)
    {
        $matched = [];
        // @todo show all availables series if no author is selected?
        foreach ($match->getCachedSeriesTitles() as $id => $title) {
            $matched[] = [
                'id' => $id,
                'title' => $title,
                'count' => '',
                'description' => '',
                'link' => 'https://www.goodreads.com/series/' . $id,
            ];
        }
        $offset ??= 0;
        if (count($matched) > $this->db->limit) {
            $matched = array_slice($matched, $offset, $this->db->limit);
        }
        return $matched;
    }
}
