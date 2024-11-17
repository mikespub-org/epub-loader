<?php
/**
 * WikiDataHandler class
 */

namespace Marsender\EPubLoader\Metadata\WikiData;

use Marsender\EPubLoader\Handlers\MetadataHandler;
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
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
            case 'wd_series':
                $seriesId = $this->request->getId('seriesId');
                if (!WikiDataMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $findLinks = $this->request->get('findLinks', false);
                $result = $this->wd_series($authorId, $seriesId, $matchId, $findLinks);
                break;
            case 'wd_books':
                $seriesId = $this->request->getId('seriesId');
                $bookId = $this->request->getId('bookId');
                if (!WikiDataMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $result = $this->wd_books($authorId, $seriesId, $bookId, $matchId);
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
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');
        $result = $this->authors($authorId, $sort, $offset);
        $authorId = $result['authorId'];
        /** @var AuthorInfo|null $authorInfo */
        $authorInfo = $result['authorInfo'];

        // Find matchId from author link
        if (empty($matchId) && !empty($authorInfo) && WikiDataMatch::isValidLink($authorInfo->link)) {
            $matchId = WikiDataMatch::entity($authorInfo->link);
        }

        // Find match on Wikidata
        $wikimatch = new WikiDataMatch($this->cacheDir);

        if (empty($matchId) && !empty($findLinks)) {
            $result['authors'] = $this->findAuthorLinks($wikimatch, $result['authors']);
        }
        $matched = null;
        if (!empty($matchId)) {
            // @todo create $matched based on $matchId
            // Update the author link
            if (!is_null($authorId) && empty($authorInfo->link)) {
                $link = WikiDataMatch::link($matchId);
                if (!$this->db->setAuthorLink($authorId, $link)) {
                    $this->addError($this->dbFileName, "Failed updating link {$link} for authorId {$authorId}");
                }
                // @todo $result['authors'][$authorId]['link'] = $link;
            }
        } elseif (!empty($authorId) && !empty($authorInfo)) {
            $name = $authorInfo->name;
            $matched = $wikimatch->findAuthors($name);
            // Find works from author for 1st match
            if (count($matched) > 0) {
                $firstId = array_key_first($matched);
                $matched[$firstId]['entries'] = $wikimatch->findWorksByAuthorProperty($authorInfo);
            }
        }

        $result['matched'] = $matched;
        $result['matchId'] = $matchId;

        // Return info
        return $result;
    }

    /**
     * Summary of findAuthorLinks
     * @param WikiDataMatch $match
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
                    $authors[$id]['link'] = WikiDataMatch::link($matchId);
                }
            }
        }
        // we need to run this again here...
        $authors = $this->addAuthorLinks($authors);
        return $authors;
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
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');
        $result = $this->books($authorId, $seriesId, $bookId, $sort, $offset);
        $authorId = $result['authorId'];
        /** @var AuthorInfo|null $authorInfo */
        $authorInfo = $result['authorInfo'];
        $seriesId = $result['seriesId'];
        /** @var BookInfo|null $bookInfo */
        $bookInfo = $result['bookInfo'];

        // Update the book identifier
        if (!is_null($bookId) && !is_null($matchId)) {
            if (!$this->updateBookIdentifier('wd', $bookId, $matchId)) {
                $this->addError($this->dbFileName, "Failed updating wd identifier for bookId {$bookId} to {$matchId}");
            }
        }

        // Find match on Wikidata
        $wikimatch = new WikiDataMatch($this->cacheDir);

        $matched = null;
        if (!empty($bookId) && !empty($bookInfo)) {
            $matched = $wikimatch->findWorksByTitle($bookInfo->title);
        } elseif (!empty($seriesId)) {
            // @todo use author here too!?
            $matched = $wikimatch->findWorksByAuthorProperty($authorInfo);
            //$matched = array_merge($matched, $wikimatch->findWorksByAuthorName($authorInfo));
            if (empty($matched)) {
                $matched = $wikimatch->findWorksByAuthorName($authorInfo->name);
            }
        } else {
            $matched = $wikimatch->findWorksByAuthorProperty($authorInfo);
            //$matched = array_merge($matched, $wikimatch->findWorksByAuthorName($authorInfo));
            if (empty($matched)) {
                $matched = $wikimatch->findWorksByAuthorName($authorInfo->name);
            }
        }

        // exact match only here - see calibre metadata plugins for more advanced features
        $result['books'] = $this->matchBookTitles($result['books'], $matched);

        $result['matched'] = $matched;
        $result['matchId'] = $matchId;
        //$result['authId'] = $authId;
        //$result['serId'] = $serId;
        $result['identifierType'] = 'wd';

        // Return info
        return $result;
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
        foreach ($matched as $match) {
            if (array_key_exists($match['label'], $titles)) {
                $id = $titles[$match['label']];
                if (empty($books[$id]['identifiers']['wd']) || $books[$id]['identifiers']['wd']['value'] != $match['id']) {
                    $books[$id]['identifiers']['ID:'] = ['id' => 0, 'book' => $id, 'type' => '* wd', 'value' => $match['id'], 'url' => WikiDataMatch::link($match['id'])];
                } else {
                    $books[$id]['identifiers']['ID:'] = ['id' => 0, 'book' => $id, 'type' => '* wd', 'value' => '='];
                }
                unset($titles[$match['label']]);
            }
        }
        return $books;
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
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');
        $result = $this->series($authorId, $seriesId, $sort, $offset);
        // series can have multiple authors
        $authorId = $result['authorId'];
        /** @var AuthorInfo|null $authorInfo */
        $authorInfo = $result['authorInfo'];
        $seriesId = $result['seriesId'];
        /** @var SeriesInfo|null $seriesInfo */
        $seriesInfo = $result['seriesInfo'];

        // Find matchId from series link
        if (empty($matchId) && !empty($seriesInfo) && WikiDataMatch::isValidLink($seriesInfo->link)) {
            $matchId = WikiDataMatch::entity($seriesInfo->link);
        }

        // Find match on Wikidata
        $wikimatch = new WikiDataMatch($this->cacheDir);

        if (empty($matchId) && !empty($findLinks)) {
            $result['series'] = $this->findSeriesLinks($wikimatch, $result['series']);
        }
        $matched = null;
        if (!empty($seriesId) && !empty($seriesInfo)) {
            $name = $seriesInfo->title;
            $matched = $wikimatch->findSeriesByName($name);
            // Update the series link
            if (!empty($matchId) && empty($seriesInfo->link)) {
                $link = WikiDataMatch::link($matchId);
                if (!$this->db->setSeriesLink($seriesId, $link)) {
                    $this->addError($this->dbFileName, "Failed updating link {$link} for seriesId {$seriesId}");
                    //return null;
                }
                // @todo $result['series'][$seriesId]['link'] = $link;
            }
        } elseif (count($result['series']) > 0 && !empty($authorId) && !empty($authorInfo)) {
            $matched = $wikimatch->findSeriesByAuthor($authorInfo);
        }

        $result['matched'] = $matched;
        $result['matchId'] = $matchId;

        // Return info
        return $result;
    }

    /**
     * Summary of findSeriesLinks
     * @param WikiDataMatch $match
     * @param array<mixed> $series
     * @return array<mixed>
     */
    protected function findSeriesLinks($match, $series)
    {
        foreach ($series as $id => $serie) {
            // @todo look up potential series by (cached) title / author(s)
            if (empty($serie['link'])) {
                //$found = $match->findSeriesByName($serie['name']);
            }
        }
        // we need to run this again here...
        //$series = $this->addSeriesLinks($series);
        return $series;
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
        $wikimatch = new WikiDataMatch($this->cacheDir);
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
                //$matched = $wikimatch->findSeriesByName($query);
            }
        }
        if (!empty($authorId) && empty($entityId)) {
            $authors = $this->db->getAuthors($authorId);
            $authorInfo = $authors[$authorId];
            $entityId = $wikimatch->findAuthorId($authorInfo);
        }
        if (!empty($entityId)) {
            $entity = $wikimatch->getEntity($entityId);
        }
        $authorList = $this->getAuthorList();
        $seriesList = $this->getSeriesList($authorId);

        $callback = false;
        if (!empty($entityId) && !empty($entity)) {
            $callback = $this->check_callback($entity, $wikimatch);
        }

        // Return info
        return [
            'entity' => $entity,
            'entityId' => $entityId,
            'authorId' => $authorId,
            'seriesId' => $seriesId,
            'authors' => $authorList,
            'series' => $seriesList,
            'callback' => $callback,
        ];
    }

    /**
     * Summary of check_callback
     * @param mixed $entity
     * @param WikiDataMatch $match
     * @return mixed
     */
    public function check_callback($entity, $match)
    {
        $parsed = WikiDataCache::parseEntity($entity);
        if (empty($parsed)) {
            return false;
        }
        return match ($parsed['type']) {
            'author' => $this->callSetAuthorInfo($parsed, $match),
            'book' => $this->callSetBookInfo($parsed, $match),
            'series' => $this->callSetSeriesInfo($parsed, $match),
            default => false,
        };
    }

    /**
     * Summary of callSetAuthorInfo
     * @param mixed $parsed
     * @param WikiDataMatch $match
     * @return mixed
     */
    public function callSetAuthorInfo($parsed, $match)
    {
        // check if we have a callback + the calibre id for it
        $authorId = $this->request->get('authorId');
        $callbacks = $this->request->getCallbacks();
        $callback = $callbacks['setAuthorInfo'] ?? '';
        if (empty($authorId) || empty($callback)) {
            return false;
        }
        $basePath = $this->cacheDir . '/wikidata';
        $authorInfo = WikiDataImport::loadAuthor($basePath, $parsed, $match->getCache());
        if (empty($authorInfo)) {
            return false;
        }
        $result = $callback($authorId, $authorInfo);
        return $result;
    }

    /**
     * Summary of callSetBookInfo
     * @param mixed $parsed
     * @param WikiDataMatch $match
     * @return mixed
     */
    public function callSetBookInfo($parsed, $match)
    {
        // check if we have a callback + the calibre id for it
        $bookId = $this->request->get('bookId');
        $callbacks = $this->request->getCallbacks();
        $callback = $callbacks['setBookInfo'] ?? '';
        if (empty($bookId) || empty($callback)) {
            return false;
        }
        $basePath = $this->cacheDir . '/wikidata';
        $bookInfo = WikiDataImport::load($basePath, $parsed, $match->getCache());
        if (empty($bookInfo)) {
            return false;
        }
        $result = $callback($bookId, $bookInfo);
        return $result;
    }

    /**
     * Summary of callSetSeriesInfo
     * @param mixed $parsed
     * @param WikiDataMatch $match
     * @return mixed
     */
    public function callSetSeriesInfo($parsed, $match)
    {
        // check if we have a callback + the calibre id for it
        $seriesId = $this->request->get('seriesId');
        $callbacks = $this->request->getCallbacks();
        $callback = $callbacks['setSeriesInfo'] ?? '';
        if (empty($seriesId) || empty($callback)) {
            return false;
        }
        $basePath = $this->cacheDir . '/wikidata';
        $seriesInfo = WikiDataImport::loadSeries($basePath, $parsed, $match->getCache());
        if (empty($seriesInfo)) {
            return false;
        }
        $result = $callback($seriesId, $seriesInfo);
        return $result;
    }
}
