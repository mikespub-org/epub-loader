<?php
/**
 * WikiDataCache class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\WikiData;

use Marsender\EPubLoader\Metadata\BaseCache;
use Marsender\EPubLoader\Metadata\BaseMatch;
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
            'Q16017119' => 'collective pseudonym',
            'Q127843' => 'pen name',
            'Q61002' => 'pseudonym',
            'Q10648343' => 'duo',
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
            $entry = $this->loadCache($cacheFile);
            return match ($cacheType) {
                'authors' => $this->formatSearch($entry, $urlPrefix),
                'works/title' => $this->formatSearch($entry, $urlPrefix),
                'works/author' => $this->formatSearch($entry, $urlPrefix),
                'works/name' => $this->formatSearch($entry, $urlPrefix),
                'series/title' => $this->formatSearch($entry, $urlPrefix),
                'series/author' => $this->formatSearch($entry, $urlPrefix),
                'entities' => $this->formatEntity($entry, $urlPrefix),
                default => $entry,
            };
        }
        return null;
    }

    /**
     * Summary of formatSearch
     * @param array<mixed>|null $entry
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function formatSearch($entry, $urlPrefix)
    {
        if (empty($entry) || is_null($urlPrefix)) {
            return $entry;
        }
        $result = self::parseSearchResult($entry);
        // <a href="{{endpoint}}/{{action}}/{{dbNum}}/{{cacheName}}/{{cacheType}}?entry={{entry}}">{{entry}}</a>
        foreach ($result as $id => $item) {
            $entityId = $item->id;
            $cacheFile = $this->getEntity($entityId);
            if ($this->hasCache($cacheFile)) {
                $result[$id]->id = "<a href='{$urlPrefix}entities?entry={$entityId}'>{$entityId}</a>";
            } else {
                $result[$id]->id = "<a href='{$urlPrefix}entities?entry={$entityId}'>{$entityId}</a> ?";
            }
        }
        return $result;
    }

    /**
     * Summary of formatEntity
     * @param array<mixed>|null $entry
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function formatEntity($entry, $urlPrefix)
    {
        if (is_null($entry) || is_null($urlPrefix)) {
            return $entry;
        }
        $entity = self::parseEntity($entry);
        if (empty($entity)) {
            return null;
        }
        $entity = $this->formatIdentifiers($entity, $urlPrefix);
        $entity = match ($entity['type']) {
            'author' => $this->formatAuthor($entity, $urlPrefix),
            'book' => $this->formatBook($entity, $urlPrefix),
            'series' => $this->formatSeries($entity, $urlPrefix),
            'publisher' => $this->formatPublisher($entity, $urlPrefix),
            default => throw new Exception('Unknown entity type ' . $entity['type']),
        };
        $entity = $this->formatProperties($entity, $urlPrefix);
        return $entity;
    }

    /**
     * Summary of formatIdentifiers
     * @param array<mixed>|null $entity
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function formatIdentifiers($entity, $urlPrefix)
    {
        if (empty($entity['identifiers'])) {
            return $entity;
        }
        foreach ($entity['identifiers'] as $type => $value) {
            $link = BaseMatch::getTypeLink($type, $value);
            if (!empty($link)) {
                $entity['identifiers'][$type] = "<a rel='external' target='_blank' href='{$link}'>{$value}</a>";
            }
        }
        return $entity;
    }

    /**
     * Summary of formatProperties
     * @param array<mixed>|null $entity
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function formatProperties($entity, $urlPrefix)
    {
        if (empty($entity['properties'])) {
            unset($entity['properties']);
            return $entity;
        }
        $propertyKeys = array_keys($entity['properties']);
        $properties = [];
        foreach ($propertyKeys as $key) {
            $property = $entity['properties'][$key];
            if (empty($property['values'])) {
                continue;
            }
            // from "values": [{"id": "80827", "label": "80827", "qualifiers": []}] to "value": "80827"
            if (count($property['values']) == 1 && empty($property['values'][0]['qualifiers']) && $property['values'][0]['id'] == $property['values'][0]['label']) {
                $properties[$property['id'] . ': ' . $property['label']] = $property['values'][0]['label'];
                continue;
            }
            $values = [];
            foreach ($property['values'] as $id => $item) {
                if (empty($item['qualifiers'])) {
                    if ($item['id'] == $item['label']) {
                        $values[$item['label']] = [];
                    } else {
                        $values[$item['id'] . ': ' . $item['label']] = [];
                    }
                    continue;
                }
                $qualifiers = [];
                foreach ($item['qualifiers'] as $q => $qualifier) {
                    $qualifiers[$qualifier['id'] . ': ' . $qualifier['label']] = $qualifier['value'];
                }
                if ($item['id'] == $item['label']) {
                    $values[$item['label']] = $qualifiers;
                } else {
                    $values[$item['id'] . ': ' . $item['label']] = $qualifiers;
                }
            }
            $properties[$property['id'] . ': ' . $property['label']] = $values;
        }
        unset($entity['properties']);
        $entity['TODO'] = 'parse';
        $entity['properties'] = $properties;
        return $entity;
    }

    /**
     * Summary of formatAuthor
     * @param array<mixed>|null $entity
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function formatAuthor($entity, $urlPrefix)
    {
        foreach (['genre'] as $key) {
            if (empty($entity[$key])) {
                continue;
            }
            foreach ($entity[$key] as $id => $item) {
                if (empty($item['id'])) {
                    continue;
                }
                $entityId = $item['id'];
                $cacheFile = $this->getEntity($entityId);
                if ($this->hasCache($cacheFile)) {
                    $entity[$key][$id]['id'] = "<a href='{$urlPrefix}entities?entry={$entityId}'>{$entityId}</a>";
                } else {
                    $entity[$key][$id]['id'] = "<a href='{$urlPrefix}entities?entry={$entityId}'>{$entityId}</a> ?";
                }
                if (empty($item['qualifiers'])) {
                    unset($entity[$key][$id]['qualifiers']);
                }
            }
        }
        return $entity;
    }

    /**
     * Summary of formatBook
     * @param array<mixed>|null $entity
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function formatBook($entity, $urlPrefix)
    {
        foreach (['author', 'series', 'genre'] as $key) {
            if (empty($entity[$key])) {
                continue;
            }
            foreach ($entity[$key] as $id => $item) {
                if (empty($item['id'])) {
                    continue;
                }
                $entityId = $item['id'];
                $cacheFile = $this->getEntity($entityId);
                if ($this->hasCache($cacheFile)) {
                    $entity[$key][$id]['id'] = "<a href='{$urlPrefix}entities?entry={$entityId}'>{$entityId}</a>";
                } else {
                    $entity[$key][$id]['id'] = "<a href='{$urlPrefix}entities?entry={$entityId}'>{$entityId}</a> ?";
                }
                if (empty($item['qualifiers'])) {
                    unset($entity[$key][$id]['qualifiers']);
                }
            }
        }
        return $entity;
    }

    /**
     * Summary of formatSeries
     * @param array<mixed>|null $entity
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function formatSeries($entity, $urlPrefix)
    {
        foreach (['author', 'bookList', 'genre', 'parent'] as $key) {
            if (empty($entity[$key])) {
                continue;
            }
            foreach ($entity[$key] as $id => $item) {
                if (empty($item['id'])) {
                    continue;
                }
                $entityId = $item['id'];
                $cacheFile = $this->getEntity($entityId);
                if ($this->hasCache($cacheFile)) {
                    $entity[$key][$id]['id'] = "<a href='{$urlPrefix}entities?entry={$entityId}'>{$entityId}</a>";
                } else {
                    $entity[$key][$id]['id'] = "<a href='{$urlPrefix}entities?entry={$entityId}'>{$entityId}</a> ?";
                }
                if (empty($item['qualifiers'])) {
                    unset($entity[$key][$id]['qualifiers']);
                }
            }
        }
        return $entity;
    }

    /**
     * Summary of formatPublisher
     * @param array<mixed>|null $entity
     * @param string|null $urlPrefix
     * @return array<mixed>|null
     */
    public function formatPublisher($entity, $urlPrefix)
    {
        return $entity;
    }

    /**
     * Summary of parseSearchResult
     * @param array<mixed> $data
     * @param string $lang
     * @return array<SearchResult>
     */
    public static function parseSearchResult($data, $lang = 'en')
    {
        if (empty($data)) {
            return $data;
        }
        $result = [];
        foreach ($data as $id => $item) {
            $result[$id] = new SearchResult($item, $lang);
        }
        return $result;
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
        $data['type'] = null;
        foreach ($instanceOf['values'] as $value) {
            if (array_key_exists($value['id'], self::$instanceTypes)) {
                $data['type'] = self::$instanceTypes[$value['id']];
                $data['instance'] = self::$typeDefinitions[$data['type']][$value['id']];
                break;
            }
        }
        if (empty($data['type'])) {
            return null;
        }
        unset($data['properties']['P31']);
        // @todo parse author, book, series etc.
        $result = match ($data['type']) {
            'author' => self::parseAuthor($data),
            'book' => self::parseBook($data),
            'series' => self::parseSeries($data),
            'publisher' => self::parsePublisher($data),
            default => throw new Exception('Unknown instance type ' . $data['type']),
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
        // P18: image
        $data['cover'] = null;
        if (!empty($data['properties']['P18']) && !empty($data['properties']['P18']['values'])) {
            $data['cover'] = $data['properties']['P18']['values'][0]['label'] ?? '';
            unset($data['properties']['P18']);
        }
        // P136: genre
        $data['genre'] = null;
        if (!empty($data['properties']['P136'])) {
            $data['genre'] = $data['properties']['P136']['values'] ?? [];
            unset($data['properties']['P136']);
        }
        $data['identifiers'] = [];
        $data['identifiers']['wd'] = $data['id'];
        if (!empty($data['wiki_url'])) {
            $data['identifiers']['url'] = $data['wiki_url'];
            unset($data['wiki_url']);
        }
        $author = $data;
        $author['identifiers'] = self::addBookIdentifiers($author['identifiers'], $author['properties']);
        // @todo parse author, book, series etc.

        //unset($author['properties']);
        return $author;
    }

    /**
     * Summary of addAuthorIdentifiers
     * @param array<string, mixed> $identifiers
     * @param array<string, mixed> $properties by reference
     * @return array<string, mixed>
     */
    public static function addAuthorIdentifiers($identifiers, &$properties)
    {
        $todo = [
            'goodreads_a' => [
                'P2963',  // P2963: Goodreads author ID
            ],
            'olid' => [
                'P648',  // P648: Open Library ID
            ],
            'ltid_a' => [
                'P7400',  // P7400: LibraryThing author ID
            ],
            'isfdb_a' => [
                'P1233',  // P1233: Internet Speculative Fiction Database author ID
            ],
            'viaf' => [
                'P214',  // P214: VIAF ID
            ],
            'freebase' => [
                'P646',  // P646: Freebase ID
            ],
            'g_kgmid' => [
                'P2671',  // P2671: Google Knowledge Graph ID
            ],
        ];
        foreach ($todo as $name => $pids) {
            foreach ($pids as $pid) {
                if (!empty($properties[$pid]) && !empty($properties[$pid]['values'])) {
                    $identifiers[$name] = $properties[$pid]['values'][0]['label'] ?? '';
                    unset($properties[$pid]);
                    break;
                }
            }
        }
        return $identifiers;
    }

    /**
     * Summary of parseCommon
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public static function parseCommon($data)
    {
        // P50: author
        $data['author'] = null;
        if (!empty($data['properties']['P50'])) {
            $data['author'] = $data['properties']['P50']['values'] ?? [];
            unset($data['properties']['P50']);
        }
        // P407: language of work or name
        $data['language'] = null;
        if (!empty($data['properties']['P407']) && !empty($data['properties']['P407']['values'])) {
            $data['language'] = $data['properties']['P407']['values'][0]['label'] ?? '';
            unset($data['properties']['P407']);
        }
        // P577: publication date
        $data['published'] = null;
        if (!empty($data['properties']['P577'])) {
            $dates = $data['properties']['P577']['values'];
            if (!empty($dates)) {
                $data['published'] = explode('T', $dates[0]['label'] ?? '')[0];
            }
            unset($data['properties']['P577']);
        }
        // P1476: title
        if (!empty($data['properties']['P1476']) && !empty($data['properties']['P1476']['values'])) {
            $data['title'] = $data['properties']['P1476']['values'][0]['label'] ?? '';
            unset($data['properties']['P1476']);
        }
        // P123: publisher
        $data['publisher'] = null;
        if (!empty($data['properties']['P123']) && !empty($data['properties']['P123']['values'])) {
            $data['publisher'] = $data['properties']['P123']['values'][0]['label'] ?? '';
            unset($data['properties']['P123']);
        }
        // P18: image
        $data['cover'] = null;
        if (!empty($data['properties']['P18']) && !empty($data['properties']['P18']['values'])) {
            $data['cover'] = $data['properties']['P18']['values'][0]['label'] ?? '';
            unset($data['properties']['P18']);
        }
        // P136: genre
        $data['genre'] = null;
        if (!empty($data['properties']['P136'])) {
            $data['genre'] = $data['properties']['P136']['values'] ?? [];
            unset($data['properties']['P136']);
        }
        $data['identifiers'] = [];
        $data['identifiers']['wd'] = $data['id'];
        if (!empty($data['wiki_url'])) {
            $data['identifiers']['url'] = $data['wiki_url'];
            unset($data['wiki_url']);
        }
        return $data;
    }

    /**
     * Summary of parseBook
     * @param array<mixed> $data
     * @return array<mixed>|null
     */
    public static function parseBook($data)
    {
        $book = self::parseCommon($data);
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
            unset($book['properties']['P179']);
        }
        // P7937: form of creative work
        $book['format'] = null;
        if (!empty($book['properties']['P7937']) && !empty($book['properties']['P7937']['values'])) {
            $book['format'] = $book['properties']['P7937']['values'][0]['label'] ?? '';
            unset($book['properties']['P7937']);
        }
        $book['identifiers'] = self::addBookIdentifiers($book['identifiers'], $book['properties']);
        // @todo other properties

        //unset($book['properties']);
        return $book;
    }

    /**
     * Summary of addBookIdentifiers
     * @param array<string, mixed> $identifiers
     * @param array<string, mixed> $properties by reference
     * @return array<string, mixed>
     */
    public static function addBookIdentifiers($identifiers, &$properties)
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
            'viaf' => [
                'P214',  // P214: VIAF ID
            ],
            'freebase' => [
                'P646',  // P646: Freebase ID
            ],
            'g_kgmid' => [
                'P2671',  // P2671: Google Knowledge Graph ID
            ],
        ];
        foreach ($todo as $name => $pids) {
            foreach ($pids as $pid) {
                if (!empty($properties[$pid]) && !empty($properties[$pid]['values'])) {
                    $identifiers[$name] = $properties[$pid]['values'][0]['label'] ?? '';
                    unset($properties[$pid]);
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
        $series = self::parseCommon($data);
        // P527: has part(s)
        $series['bookList'] = null;
        if (!empty($series['properties']['P527'])) {
            $series['bookList'] = $series['properties']['P527']['values'] ?? [];
            unset($series['properties']['P527']);
        }
        // P1114: quantity
        $series['numWorks'] = null;
        if (!empty($series['properties']['P1114']) && !empty($series['properties']['P1114']['values'])) {
            $series['numWorks'] = $series['properties']['P1114']['values'][0]['label'] ?? '';
            unset($series['properties']['P1114']);
        }
        // P361: part of
        $series['parent'] = null;
        if (!empty($series['properties']['P361'])) {
            $series['parent'] = $series['properties']['P361']['values'] ?? [];
            unset($series['properties']['P361']);
        }
        $series['identifiers'] = self::addSeriesIdentifiers($series['identifiers'], $series['properties']);
        // @todo parse author, book, series etc.

        //unset($series['properties']);
        return $series;
    }

    /**
     * Summary of addSeriesIdentifiers
     * @param array<string, mixed> $identifiers
     * @param array<string, mixed> $properties by reference
     * @return array<string, mixed>
     */
    public static function addSeriesIdentifiers($identifiers, &$properties)
    {
        $todo = [
            'goodreads_s' => [
                'P6947',  // P6947: Goodreads series ID
            ],
            'ltid_s' => [
                'P8513',  // P8513: LibraryThing series ID
            ],
            'isfdb_s' => [
                'P1235',  // P1235: ISFDB series ID
            ],
            'viaf' => [
                'P214',  // P214: VIAF ID
            ],
            'freebase' => [
                'P646',  // P646: Freebase ID
            ],
            'g_kgmid' => [
                'P2671',  // P2671: Google Knowledge Graph ID
            ],
        ];
        foreach ($todo as $name => $pids) {
            foreach ($pids as $pid) {
                if (!empty($properties[$pid]) && !empty($properties[$pid]['values'])) {
                    $identifiers[$name] = $properties[$pid]['values'][0]['label'] ?? '';
                    unset($properties[$pid]);
                    break;
                }
            }
        }
        return $identifiers;
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
        //unset($publisher['properties']);
        return $publisher;
    }
}
