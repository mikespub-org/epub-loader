<?php
/**
 * WikiDataCache class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\WikiData;

use Marsender\EPubLoader\Metadata\BaseCache;
use Wikidata\Entity;
use Wikidata\SearchResult;
use Exception;

class WikiDataCache extends BaseCache
{
    public const CACHE_TYPES = [
        'wikidata/authors',
        'wikidata/works/author',
        'wikidata/works/name',
        'wikidata/works/title',
        'wikidata/series/author',
        'wikidata/series/title',
        'wikidata/entities',
    ];
    /** @var array<string, mixed> */
    public static array $typeDefinitions = [
        'author' => [
            'Q5' => 'human',
        ],
        'book' => [
            'Q7725634' => 'literary work',
            'Q47461344' => 'written work',
            // 'Q3331189' => 'version, edition, or translation',
            // 'Q1279564' => 'short story collection',
            // 'Q49084' => 'short story',
        ],
        'series' => [
            'Q1667921' => 'novel series',
            'Q277759' => 'book series',
            // 'Q13593966' => 'literary trilogy',
        ],
        'publisher' => [
            'Q2085381' => 'publisher',
        ],
    ];
    /** @var array<string, string> */
    public static array $instanceTypes = [
        'Q5' => 'author',
        'Q7725634' => 'book',
        'Q47461344' => 'book',
        'Q1667921' => 'series',
        'Q277759' => 'series',
        'Q2085381' => 'publisher',
    ];

    /**
     * Summary of getAuthorQuery
     * Path: '/wikidata/authors/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getAuthorQuery($query, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/wikidata/authors/' . $query . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorQueries
     * Path: '/wikidata/authors/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getAuthorQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/wikidata/authors/';
        return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getAuthorWork
     * Path: '/wikidata/works/author/' . $authorId . '.' . $lang . '.' . $limit . '.json'
     * @param string $authorId
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 10)
     * @return string
     */
    public function getAuthorWork($authorId, $lang = 'en', $limit = 100)
    {
        $cacheFile = $this->cacheDir . '/wikidata/works/author/' . $authorId . '.' . $lang . '.' . $limit . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorWorkIds
     * Path: '/wikidata/works/author/'
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 100)
     * @return array<string>
     */
    public function getAuthorWorkIds($lang = 'en', $limit = 100)
    {
        $baseDir = $this->cacheDir . '/wikidata/works/author/';
        return parent::getFiles($baseDir, '*.' . $lang . '.' . $limit . '.json', true);
    }

    /**
     * Summary of getAuthorWorkQuery
     * Path: '/wikidata/works/name/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getAuthorWorkQuery($query, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/wikidata/works/name/' . $query . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorWorkQueries
     * Path: '/wikidata/works/name/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getAuthorWorkQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/wikidata/works/name/';
        return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getTitleQuery
     * Path: '/wikidata/works/title/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getTitleQuery($query, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/wikidata/works/title/' . $query . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getTitleQueries
     * Path: '/wikidata/works/title/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getTitleQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/wikidata/works/title/';
        return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getAuthorSeries
     * Path: '/wikidata/series/author/' . $authorId . '.' . $lang . '.' . $limit . '.json'
     * @param string $authorId
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 10)
     * @return string
     */
    public function getAuthorSeries($authorId, $lang = 'en', $limit = 100)
    {
        $cacheFile = $this->cacheDir . '/wikidata/series/author/' . $authorId . '.' . $lang . '.' . $limit . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getAuthorSeriesIds
     * Path: '/wikidata/series/author/'
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 100)
     * @return array<string>
     */
    public function getAuthorSeriesIds($lang = 'en', $limit = 100)
    {
        $baseDir = $this->cacheDir . '/wikidata/series/author/';
        return parent::getFiles($baseDir, '*.' . $lang . '.' . $limit . '.json', true);
    }

    /**
     * Summary of getSeriesQuery
     * Path: '/wikidata/series/title/' . $query . '.' . $lang . '.json'
     * @param string $query
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getSeriesQuery($query, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/wikidata/series/title/' . $query . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getSeriesQueries
     * Path: '/wikidata/series/title/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getSeriesQueries($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/wikidata/series/title/';
        return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getEntity
     * Path: '/wikidata/entities/' . $entityId . '.' . $lang . '.json'
     * @param string $entityId
     * @param string $lang Language (default: en)
     * @return string
     */
    public function getEntity($entityId, $lang = 'en')
    {
        $cacheFile = $this->cacheDir . '/wikidata/entities/' . $entityId . '.' . $lang . '.json';
        return $cacheFile;
    }

    /**
     * Summary of getEntityIds
     * Path: '/wikidata/entities/'
     * @param string $lang Language (default: en)
     * @return array<string>
     */
    public function getEntityIds($lang = 'en')
    {
        $baseDir = $this->cacheDir . '/wikidata/entities/';
        return parent::getFiles($baseDir, '*.' . $lang . '.json', true);
    }

    /**
     * Summary of getStats
     * @return array<mixed>
     */
    public function getStats()
    {
        return [
            'authors' => count($this->getAuthorQueries()),
            'works/title' => count($this->getTitleQueries()),
            'works/author' => count($this->getAuthorWorkIds()),
            'works/name' => count($this->getAuthorWorkQueries()),
            'series/title' => count($this->getSeriesQueries()),
            'series/author' => count($this->getAuthorSeriesIds()),
            'entities' => count($this->getEntityIds()),
        ];
    }

    /**
     * Summary of getEntries
     * @param string $cacheType
     * @param int|null $offset
     * @return array<mixed>
     */
    public function getEntries($cacheType, $offset = null)
    {
        $offset ??= 0;
        $entries = match ($cacheType) {
            'authors' => $this->getAuthorQueries(),
            'works/title' => $this->getTitleQueries(),
            'works/author' => $this->getAuthorWorkIds(),
            'works/name' => $this->getAuthorWorkQueries(),
            'series/title' => $this->getSeriesQueries(),
            'series/author' => $this->getAuthorSeriesIds(),
            'entities' => $this->getEntityIds(),
            default => throw new Exception('Invalid cache type'),
        };
        $entries = array_slice($entries, $offset, static::$limit);
        return $entries;
    }

    /**
     * Summary of getEntry
     * @param string $cacheType
     * @param string $cacheEntry
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function getEntry($cacheType, $cacheEntry, $urlPrefix = null)
    {
        $cacheFile = match ($cacheType) {
            'authors' => $this->getAuthorQuery($cacheEntry),
            'works/title' => $this->getTitleQuery($cacheEntry),
            'works/author' => $this->getAuthorWork($cacheEntry),
            'works/name' => $this->getAuthorWorkQuery($cacheEntry),
            'series/title' => $this->getSeriesQuery($cacheEntry),
            'series/author' => $this->getAuthorSeries($cacheEntry),
            'entities' => $this->getEntity($cacheEntry),
            default => throw new Exception('Invalid cache type'),
        };
        if ($this->hasCache($cacheFile)) {
            return $this->loadCache($cacheFile);
        }
        return null;
    }

    /**
     * Summary of parseSearchResult
     * @param array<mixed> $data
     * @param string $lang
     * @return SearchResult
     */
    public static function parseSearchResult($data, $lang = 'en')
    {
        return new SearchResult($data, $lang);
    }

    /**
     * Summary of parseEntity
     * @param array<mixed> $data
     * @param string $lang
     * @return Entity|array<mixed>|null
     */
    public static function parseEntity($data, $lang = 'en')
    {
        if (empty($data['properties']) || empty($data['properties']['P31'])) {
            return null;
        }
        $instanceOf = $data['properties']['P31'];
        if (empty($instanceOf['values'])) {
            return null;
        }
        $instanceType = null;
        foreach ($instanceOf['values'] as $value) {
            if (array_key_exists($value['id'], self::$instanceTypes)) {
                $instanceType = self::$instanceTypes[$value['id']];
                break;
            }
        }
        if (empty($instanceType)) {
            return null;
        }
        $data['type'] = $instanceType;
        // @todo parse author, book, series etc.
        $result = match ($instanceType) {
            'author' => self::parseAuthor($data),
            'book' => self::parseBook($data),
            'series' => self::parseSeries($data),
            'publisher' => self::parsePublisher($data),
            default => throw new Exception('Unknown instance type ' . $instanceType),
        };
        return $result;
        //$entity = new Entity($data, $lang);
        // @todo this generates warnings for missing prop, propertyLabel, qualifier etc.
        //$entity->parseProperties($data['properties'] ?? []);
        //return $entity;
    }

    /**
     * Summary of parseAuthor
     * @param array<mixed> $data
     * @return array<mixed>|null
     */
    public static function parseAuthor($data)
    {
        $author = $data;
        // @todo parse author, book, series etc.
        unset($author['properties']);
        return $author;
    }

    /**
     * Summary of parseBook
     * @param array<mixed> $data
     * @return array<mixed>|null
     */
    public static function parseBook($data)
    {
        $book = $data;
        // P50: author
        $book['author'] = null;
        if (!empty($book['properties']['P50'])) {
            $book['author'] = $book['properties']['P50']['values'] ?? [];
        }
        // P407: language of work or name
        $book['language'] = null;
        if (!empty($book['properties']['P407']) && !empty($book['properties']['P407']['values'])) {
            $book['language'] = $book['properties']['P407']['values'][0]['label'] ?? '';
        }
        // P577: publication date
        $book['published'] = null;
        if (!empty($book['properties']['P577'])) {
            $dates = $book['properties']['P577']['values'];
            if (!empty($dates)) {
                $book['published'] = explode('T', $dates[0]['label'] ?? '')[0];
            }
        }
        // P1476: title
        //if (!empty($book['properties']['P1476'])) {
        //    $book['title'] = $book['properties']['P1476']['values'] ?? [];
        //}
        // P179: part of the series
        $book['series'] = null;
        if (!empty($book['properties']['P179'])) {
            $book['series'] = $book['properties']['P179']['values'] ?? [];
            foreach ($book['series'] as $id => $series) {
                $series['qualifiers'] ??= [];
                foreach ($series['qualifiers'] as $qualifier) {
                    if ($qualifier['id'] == 'P1545') {
                        $book['series'][$id]['index'] = $qualifier['value'];
                    }
                }
                unset($book['series'][$id]['qualifiers']);
            }
        }
        // P123: publisher
        $book['publisher'] = null;
        if (!empty($book['properties']['P123']) && !empty($book['properties']['P123']['values'])) {
            $book['publisher'] = $book['properties']['P123']['values'][0]['label'] ?? '';
        }
        // P18: image
        $book['cover'] = null;
        if (!empty($book['properties']['P18']) && !empty($book['properties']['P18']['values'])) {
            $book['cover'] = $book['properties']['P18']['values'][0]['label'] ?? '';
        }
        // P136: genre
        $book['genre'] = null;
        if (!empty($book['properties']['P136'])) {
            $book['genre'] = $book['properties']['P136']['values'] ?? [];
        }
        // P7937: form of creative work
        $book['format'] = null;
        if (!empty($book['properties']['P7937']) && !empty($book['properties']['P7937']['values'])) {
            $book['format'] = $book['properties']['P7937']['values'][0]['label'] ?? '';
        }
        $book['identifiers'] = [];
        $book['identifiers']['wd'] = $book['id'];
        if (!empty($book['wiki_url'])) {
            $book['identifiers']['url'] = $book['wiki_url'];
        }
        $book['identifiers'] = self::addBookIdentifiers($book['identifiers'], $book['properties']);
        // @todo other properties

        unset($book['properties']);
        return $book;
    }

    /**
     * Summary of addBookIdentifiers
     * @param array<string, mixed> $identifiers
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    public static function addBookIdentifiers($identifiers, $properties)
    {
        $todo = [
            'isbn' => [
                'P212',  // P212: ISBN-13
                'P957',  // P957: ISBN-10
            ],
            'goodreads' => [
                'P8383',  // P8383: Goodreads work ID
                'P2969',  // P2969: Goodreads version\/edition ID
            ],
            'olid' => [
                'P648',  // P648: Open Library ID
            ],
            'ltid' => [
                'P1085',  // P1085: LibraryThing work ID
            ],
            'isfdb' => [
                'P1274',  // P1274: ISFDB title ID
            ],
        ];
        foreach ($todo as $name => $pids) {
            foreach ($pids as $pid) {
                if (!empty($properties[$pid]) && !empty($properties[$pid]['values'])) {
                    $identifiers[$name] = $properties[$pid]['values'][0]['id'] ?? '';
                    break;
                }
            }
        }
        return $identifiers;
    }

    /**
     * Summary of parseSeries
     * @param array<mixed> $data
     * @return array<mixed>|null
     */
    public static function parseSeries($data)
    {
        $series = $data;
        // @todo parse author, book, series etc.
        unset($series['properties']);
        return $series;
    }

    /**
     * Summary of parsePublisher
     * @param array<mixed> $data
     * @return array<mixed>|null
     */
    public static function parsePublisher($data)
    {
        $publisher = $data;
        // @todo parse author, book, series etc.
        unset($publisher['properties']);
        return $publisher;
    }
}
