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
            case 'gr_books':
                $seriesId = $this->request->getId('seriesId');
                $bookId = $this->request->getId('bookId');
                $result = $this->gr_books($authorId, $seriesId, $bookId, $matchId);
                break;
            case 'gr_series':
                $seriesId = $this->request->getId('seriesId');
                $findLinks = $this->request->get('findLinks', false);
                $result = $this->gr_series($authorId, $seriesId, $matchId, $findLinks);
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
        // Update the author link
        if (!is_null($authorId) && !is_null($matchId)) {
            $link = GoodReadsMatch::AUTHOR_URL . $matchId;
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

        // Find match on GoodReads
        $goodreads = new GoodReadsMatch($this->cacheDir);

        $matched = [];
        if (!empty($query)) {
            $matched = $goodreads->findAuthors($query);
            // @todo Find author with highest books count!?
            uasort($matched, function ($a, $b) {
                return count($b['books']) <=> count($a['books']);
            });
        } elseif (!empty($matchId)) {
            // remove other authors here?
            $found = $goodreads->getAuthor($matchId);
            if (!empty($found[$matchId])) {
                $matched[$matchId] = $found[$matchId];
            } else {
                //var_dump($matched);
                throw new Exception('Unable to find matching author');
            }
        } elseif ($findLinks) {
            foreach ($authors as $id => $author) {
                if (empty($author['link'])) {
                    $matchId = $goodreads->findAuthorId($author);
                    if (!empty($matchId)) {
                        $authors[$id]['link'] = GoodReadsMatch::AUTHOR_URL . $matchId;
                    }
                }
            }
        } elseif (empty($authorId)) {
            $matched = [];
            // @todo show all availables authors if no author is selected?
            foreach ($goodreads->getCachedAuthorNames() as $id => $name) {
                $matched[$id] = [
                    'id' => $id,
                    'name' => $name,
                    'books' => [],
                ];
            }
            if (count($matched) > $this->db->limit) {
                $matched = array_slice($matched, $offset, $this->db->limit);
            }
        }
        $authors = $this->addAuthorInfo($authors, $authorId, $sort, $offset);
        foreach ($matched as $key => $match) {
            foreach ($match['books'] as $id => $book) {
                $matched[$key]['books'][$id]['key'] = $book['id'];
                $matched[$key]['books'][$id]['id'] = GoodReadsMatch::bookid($book['id']);
            }
        }
        $paging = $authorId ? null : $this->db->getAuthorPaging($sort, $offset);

        // Return info
        return [
            'authors' => $authors,
            'authorId' => $authorId,
            'matched' => $matched,
            'paging' => $paging,
        ];
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
        // Get GoodReads author Id from gr_author.html (if any)
        $authId = $this->request->get('authId');
        // Get GoodReads series Id from gr_series.html (if any)
        $serId = $this->request->get('serId');
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
            if (empty($serId) && !empty($first['link']) && str_starts_with($first['link'], GoodReadsMatch::SERIES_URL)) {
                $serId = str_replace(GoodReadsMatch::SERIES_URL, '', $first['link']);
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
        if (empty($authId) && !empty($author['link']) && str_starts_with($author['link'], GoodReadsMatch::AUTHOR_URL)) {
            $authId = str_replace(GoodReadsMatch::AUTHOR_URL, '', $author['link']);
        }

        // Update the book identifier
        if (!is_null($bookId) && !is_null($matchId)) {
            $this->updateBookIdentifier('goodreads', $bookId, $matchId);
        }

        // Find match on GoodReads
        $goodreads = new GoodReadsMatch($this->cacheDir);

        $matched = null;
        if (!empty($bookId)) {
            $books = $this->db->getBooks($bookId);
            $query = $books[$bookId]['title'];
            // @todo find books by title with GoodReads?
            //$matched = $goodreads->findWorksByTitle($query, $author);
            // generic search returns 'docs' but author search returns 'entries'
            //$matched['entries'] ??= $matched['docs'];
        } elseif (!empty($seriesId)) {
            $sort = $this->request->get('sort');
            $offset = $this->request->getId('offset');
            $books = $this->db->getBooksBySeries($seriesId, $sort, $offset);
        } else {
            $sort = $this->request->get('sort');
            $offset = $this->request->getId('offset');
            $books = $this->db->getBooksByAuthor($authorId, $sort, $offset);
        }
        if (!empty($matchId)) {
            $found = $goodreads->getBook($matchId);
            $dbPath = $this->dbConfig['db_path'];
            $book = GoodReadsCache::parseBook($found);
            $info = GoodReadsImport::load($dbPath, $book);
            $matched[] = [
                'id' => GoodReadsMatch::entity($info->mUri),
                'title' => $info->mTitle,
                'url' => $info->mUri,
                'cover' => $info->mCover,
                'series' => [
                    'id' => $info->mSerieIds ? $info->mSerieIds[0] : '',
                    'title' => $info->mSerie,
                    'index' => $info->mSerieIndex,
                ],
            ];
        } elseif (!empty($authId)) {
            $found = $goodreads->getAuthor($authId);
            // remove books from other authors here?
            if (!empty($found[$authId])) {
                $matched = $found[$authId]['books'];
            }
        } elseif (!empty($serId)) {
            $found = $goodreads->getSeries($serId);
            $info = GoodReadsCache::parseSeries($found);
            // id is not available in JSON data - this must be set by caller
            $info->setId($serId);
            $matched = [];
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
        } else {
            $authId = $goodreads->findAuthorId($author);
            $found = $goodreads->getAuthor($authId);
            // remove books from other authors here?
            if (!empty($found[$authId])) {
                $matched = $found[$authId]['books'];
            }
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
        foreach ($matched as $key => $match) {
            $matched[$key]['key'] = $match['id'];
            $matched[$key]['id'] = GoodReadsMatch::bookid($match['id']);
            if (array_key_exists($match['title'], $titles)) {
                $id = $titles[$match['title']];
                if (empty($books[$id]['identifiers']['goodreads']) || $books[$id]['identifiers']['goodreads']['value'] != $matched[$key]['id']) {
                    $books[$id]['identifiers']['ID:'] = ['id' => 0, 'book' => $id, 'type' => '* goodreads', 'value' => $matched[$key]['id'], 'url' => GoodReadsMatch::link($match['id'])];
                } else {
                    $books[$id]['identifiers']['ID:'] = ['id' => 0, 'book' => $id, 'type' => '* goodreads', 'value' => '='];
                }
                unset($titles[$match['title']]);
            }
        }
        $seriesList = $this->getSeriesList($authorId);

        // Return info
        return [
            'books' => $books,
            'authorId' => $authorId,
            'seriesId' => $seriesId,
            'bookId' => $bookId,
            'matched' => $matched,
            'authors' => $authorList,
            'series' => $seriesList,
            'identifiers' => $identifierList,
            'identifierType' => 'goodreads',
            'matchId' => $matchId,
            'serId' => $serId,
        ];
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

        // Find match on GoodReads
        $goodreads = new GoodReadsMatch($this->cacheDir);

        $matched = null;
        if (!empty($seriesId)) {
            $series = $this->db->getSeries($seriesId, $authorId, null, $sort, $offset);
            // series can have multiple authors
            $first = reset($series);
            if (empty($authorId)) {
                $authorId = $first['author'];
            }
            // Update the series link
            if (!empty($matchId) && empty($first['link'])) {
                $link = GoodReadsMatch::SERIES_URL . $matchId;
                if (!$this->db->setSeriesLink($seriesId, $link)) {
                    $this->addError($this->dbFileName, "Failed updating link {$link} for seriesId {$seriesId}");
                    //return null;
                }
            } elseif (empty($matchId) && !empty($first['link']) && str_starts_with($first['link'], GoodReadsMatch::SERIES_URL)) {
                $matchId = str_replace(GoodReadsMatch::SERIES_URL, '', $first['link']);
            }
        } else {
            $series = $this->db->getSeriesByAuthor($authorId, $sort, $offset);
            if (empty($matchId) && !empty($findLinks)) {
                foreach ($series as $id => $serie) {
                    // @todo look up potential series by (cached) title / author(s)
                    if (empty($serie['link'])) {
                        $matchId = $goodreads->findCachedSeriesId($serie['name']);
                        if (!empty($matchId)) {
                            $series[$id]['link'] = GoodReadsMatch::SERIES_URL . $matchId;
                        }
                    }
                }
            }
        }
        if (!empty($matchId)) {
            $found = $goodreads->getSeries($matchId);
            if (!empty($found)) {
                $info = GoodReadsCache::parseSeries($found);
                $info->setId($matchId);
                $matched = [];
                $match = [
                    'id' => $matchId,
                    'title' => $info->getTitle(),
                    'count' => $info->getNumWorks(),
                    'description' => $info->getDescription(),
                    'link' => 'https://www.goodreads.com/series/' . $matchId,
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
            }
        } elseif (!empty($seriesId)) {
            $first = reset($series);
            $found = $goodreads->findSeriesByTitle($first['name']);
            if (!empty($found)) {
                // set in GoodReadsMatch::findSeriesByTitle()
                $matchId = $found[0][1]['id'];
                $info = GoodReadsCache::parseSeries($found);
                $info->setId($matchId);
                $matched = [];
                $match = [
                    'id' => $matchId,
                    'title' => $info->getTitle(),
                    'count' => $info->getNumWorks(),
                    'description' => $info->getDescription(),
                    'link' => 'https://www.goodreads.com/series/' . $matchId,
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
            }
        } elseif (empty($authorId)) {
            $matched = [];
            // @todo show all availables series if no author is selected?
            foreach ($goodreads->getCachedSeriesTitles() as $id => $title) {
                $matched[] = [
                    'id' => $id,
                    'title' => $title,
                    'count' => '',
                    'description' => '',
                    'link' => 'https://www.goodreads.com/series/' . $id,
                ];
            }
            if (count($matched) > $this->db->limit) {
                $matched = array_slice($matched, $offset, $this->db->limit);
            }
        }
        $series = $this->addSeriesInfo($series, $seriesId, $sort, $offset);
        $paging = ($seriesId || $authorId) ? null : $this->db->getSeriesPaging($sort, $offset);

        $authorList = $this->getAuthorList();

        // Return info
        return [
            'series' => $series,
            'authorId' => $authorId,
            'seriesId' => $seriesId,
            'matched' => $matched,
            'authors' => $authorList,
            'paging' => $paging,
        ];
    }
}
