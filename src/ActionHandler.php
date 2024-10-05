<?php
/**
 * ActionHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

use Marsender\EPubLoader\Export\BookExport;
use Marsender\EPubLoader\Import\BookImport;
use Marsender\EPubLoader\Import\CsvImport;
use Marsender\EPubLoader\Import\JsonImport;
use Marsender\EPubLoader\Metadata\Sources\GoodReadsMatch;
use Marsender\EPubLoader\Metadata\Sources\GoogleBooksMatch;
use Marsender\EPubLoader\Metadata\Sources\OpenLibraryMatch;
use Marsender\EPubLoader\Metadata\Sources\WikiDataMatch;
use Exception;

class ActionHandler
{
    /** @var array<mixed> */
    protected $dbConfig;
    /** @var CalibreDbLoader */
    protected $db;
    /** @var string */
    public $cacheDir;
    /** @var string */
    public $dbFileName;
    /** @var array<mixed> */
    protected $gErrorArray;
    /** @var RequestHandler */
    protected $request;

    /**
     * Summary of __construct
     * @param array<mixed> $dbConfig
     * @param string|null $cacheDir
     */
    public function __construct($dbConfig, $cacheDir = null)
    {
        $this->gErrorArray = [];
        $this->dbConfig = $dbConfig;
        $this->cacheDir = $cacheDir ?? dirname(__DIR__) . '/cache';
        // Init database file
        $dbPath = $this->dbConfig['db_path'];
        $this->dbFileName = $dbPath . DIRECTORY_SEPARATOR . 'metadata.db';
        // Open the database
        if (is_file($this->dbFileName)) {
            $this->db = new CalibreDbLoader($this->dbFileName);
        }
    }

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
            case 'csv_export':
                $result = $this->csv_export();
                break;
            case 'csv_import':
                $createDb = $this->dbConfig['create_db'];
                $result = $this->csv_import($createDb);
                break;
            case 'json_import':
                $createDb = $this->dbConfig['create_db'];
                $result = $this->json_import($createDb);
                break;
            case 'db_load':
                $createDb = $this->dbConfig['create_db'];
                $result = $this->db_load($createDb);
                break;
            case 'authors':
                $sort = $this->request->get('sort');
                $result = $this->authors($authorId, $sort);
                break;
            case 'wd_author':
                if (!WikiDataMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $findLinks = $this->request->get('findLinks', false);
                $result = $this->wd_author($authorId, $matchId, $findLinks);
                break;
            case 'wd_books':
                $bookId = $this->request->getId('bookId');
                if (!WikiDataMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $result = $this->wd_books($authorId, $bookId, $matchId);
                break;
            case 'wd_series':
                $seriesId = $this->request->getId('seriesId');
                if (!WikiDataMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $result = $this->wd_series($authorId, $seriesId, $matchId);
                break;
            case 'wd_entity':
                if (!WikiDataMatch::isValidEntity($matchId)) {
                    $matchId = null;
                }
                $result = $this->wd_entity($matchId, $authorId);
                break;
            case 'gb_books':
                $bookId = $this->request->getId('bookId');
                $lang = $this->request->get('lang', 'en');
                $result = $this->gb_books($authorId, $bookId, $matchId, $lang);
                break;
            case 'gb_volume':
                $lang = $this->request->get('lang', 'en');
                $result = $this->gb_volume($matchId, $lang);
                break;
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
            case 'gr_author':
                //if (!GoodReadsMatch::isValidEntity($matchId)) {
                //    $matchId = null;
                //}
                $findLinks = $this->request->get('findLinks', false);
                $result = $this->gr_author($authorId, $matchId, $findLinks);
                break;
            case 'gr_books':
                $bookId = $this->request->getId('bookId');
                //if (!WikiDataMatch::isValidEntity($matchId)) {
                //    $matchId = null;
                //}
                $result = $this->gr_books($authorId, $bookId, $matchId);
                break;
            case 'gr_series':
                $seriesId = $this->request->getId('seriesId');
                //if (!WikiDataMatch::isValidEntity($matchId)) {
                //    $matchId = null;
                //}
                $result = $this->gr_series($authorId, $seriesId, $matchId);
                break;
            case 'notes':
                $colName = $this->request->get('colName');
                $itemId = $this->request->getId('itemId');
                $html = !empty($this->request->get('html')) ? true : false;
                $result = $this->notes($colName, $itemId, $html);
                break;
            case 'resource':
                $hash = $this->request->get('hash');
                $result = $this->resource($hash);
                break;
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of csv_export
     * @return string
     */
    public function csv_export()
    {
        // Init csv file
        $dbPath = $this->dbConfig['db_path'];
        $fileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.csv';
        // Open or create the export file
        $export = new BookExport($fileName, BookExport::EXPORT_TYPE_CSV, true);
        // Add the epub files into the export file
        $epubPath = $this->dbConfig['epub_path'];
        [$message, $errors] = $export->loadFromPath($dbPath, $epubPath);
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
        // Save export
        $export->SaveToFile();
        // Display info
        return $message . '<br />';
    }

    /**
     * Summary of csv_import - @todo fix calibreFileName avoiding overlap with existing metadata.db
     * @param bool $createDb
     * @return string
     */
    public function csv_import($createDb = false)
    {
        // Init database file
        $dbPath = $this->dbConfig['db_path'];
        $calibreFileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.db';
        $bookIdsFileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_bookids.txt';
        // Open or create the database
        $import = new CsvImport($calibreFileName, $createDb, $bookIdsFileName);

        // Init csv file
        $fileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.csv';
        // Add the epub files from the import file
        [$message, $errors] = $import->loadFromCsvFile($dbPath, $fileName);
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
        // Display info
        return $message . '<br />';
    }

    /**
     * Summary of json_import - @todo fix calibreFileName avoiding overlap with existing metadata.db
     * @param bool $createDb
     * @return string
     */
    public function json_import($createDb = false)
    {
        // Init database file
        $dbPath = $this->dbConfig['db_path'];
        $calibreFileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_metadata.db';
        $bookIdsFileName = $dbPath . DIRECTORY_SEPARATOR . basename((string) $dbPath) . '_bookids.txt';
        // Open or create the database
        $import = new JsonImport($calibreFileName, $createDb, $bookIdsFileName);

        // Add the json files into the database
        $jsonPath = $this->dbConfig['json_path'] ?? $this->dbConfig['epub_path'];
        [$message, $errors] = $import->loadFromPath($dbPath, $jsonPath);
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
        // Display info
        return $message . '<br />';
    }

    /**
     * Summary of db_load
     * @param bool $createDb
     * @return string
     */
    public function db_load($createDb = false)
    {
        // Init database file
        $dbPath = $this->dbConfig['db_path'];
        $calibreFileName = $dbPath . DIRECTORY_SEPARATOR . 'metadata.db';
        $bookIdsFileName = $dbPath . DIRECTORY_SEPARATOR . 'bookids.txt';
        // Open or create the database
        $import = new BookImport($calibreFileName, $createDb, $bookIdsFileName);
        // Add the epub files into the database
        $epubPath = $this->dbConfig['epub_path'];
        [$message, $errors] = $import->loadFromPath($dbPath, $epubPath);
        if (!empty($errors)) {
            foreach ($errors as $file => $error) {
                $this->addError($file, $error);
            }
        }
        // Display info
        return $message . '<br />';
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
                return null;
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
                $firstId = array_keys($matched)[0];
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
     * @param int|null $bookId
     * @param string|null $matchId
     * @return array<mixed>|null
     */
    public function wd_books($authorId, $bookId, $matchId)
    {
        $authors = $this->db->getAuthors($authorId);
        if (empty($authorId) && empty($bookId)) {
            //$this->addError($this->dbFileName, "Please specify authorId and/or bookId");
            //return null;
            $authorId = array_keys($authors)[0];
        }

        if (count($authors) < 1) {
            $this->addError($this->dbFileName, "Please specify a valid authorId");
            return null;
        }
        $author = $authors[$authorId];

        // Update the book identifier
        if (!is_null($bookId) && !is_null($matchId)) {
            $this->updateBookIdentifier('wd', $bookId, $matchId);
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

        // Return info
        return ['books' => $books, 'authorId' => $authorId, 'author' => $authors[$authorId], 'bookId' => $bookId, 'matched' => $matched, 'authors' => $authorList];
    }

    /**
     * Summary of wd_series
     * @param int|null $authorId
     * @param int|null $seriesId
     * @param string|null $matchId
     * @return array<mixed>|null
     */
    public function wd_series($authorId, $seriesId, $matchId)
    {
        $authors = $this->db->getAuthors($authorId);
        if (empty($authorId) && empty($seriesId)) {
            //$this->addError($this->dbFileName, "Please specify authorId and/or seriesId");
            //return null;
            $authorId = array_keys($authors)[0];
        }

        if (count($authors) < 1) {
            $this->addError($this->dbFileName, "Please specify a valid authorId");
            return null;
        }
        $author = $authors[$authorId];

        // Find match on Wikidata
        $wikimatch = new WikiDataMatch($this->cacheDir);
        //$entityId = $wikimatch->findAuthorId($author);

        $matched = null;
        if (!empty($seriesId)) {
            $series = $this->db->getSeries($seriesId);
            // series can have multiple authors
            $first = array_values($series)[0];
            // Update the series link
            if (!empty($matchId) && empty($first['link'])) {
                $link = WikiDataMatch::link($matchId);
                if (!$this->db->setSeriesLink($seriesId, $link)) {
                    $this->addError($this->dbFileName, "Failed updating link {$link} for seriesId {$seriesId}");
                    return null;
                }
            }
            $query = $first['name'];
            $matched = $wikimatch->findSeriesByName($query);
        } else {
            $sort = $this->request->get('sort');
            $offset = $this->request->getId('offset');
            $series = $this->db->getSeriesByAuthor($authorId, $sort, $offset);
            if (count($series) > 0) {
                $matched = $wikimatch->findSeriesByAuthor($author);
            }
        }
        $paging = ($seriesId || $authorId) ? null : $this->db->getSeriesPaging($sort, $offset);

        $authorList = $this->getAuthorList();

        // Return info
        return ['series' => $series, 'authorId' => $authorId, 'author' => $authors[$authorId], 'seriesId' => $seriesId, 'matched' => $matched, 'authors' => $authorList, 'paging' => $paging];
    }

    /**
     * Summary of wd_entity
     * @param string|null $entityId
     * @param int|null $authorId
     * @param string|null $query
     * @return array<mixed>
     */
    public function wd_entity($entityId = null, $authorId = null, $query = null)
    {
        $entity = [];
        // Get entity on Wikidata
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

        // Return info
        return ['entity' => $entity, 'entityId' => $entityId, 'authorId' => $authorId, 'authors' => $authorList];
    }

    /**
     * Summary of gb_books
     * @param int|null $authorId
     * @param int|null $bookId
     * @param string|null $matchId
     * @param string $lang
     * @return array<mixed>|null
     */
    public function gb_books($authorId, $bookId, $matchId, $lang = 'en')
    {
        $authors = $this->db->getAuthors($authorId);
        if (empty($authorId) && empty($bookId)) {
            //$this->addError($this->dbFileName, "Please specify authorId and/or bookId");
            //return null;
            $authorId = array_keys($authors)[0];
        }

        if (count($authors) < 1) {
            $this->addError($this->dbFileName, "Please specify a valid authorId");
            return null;
        }
        $author = $authors[$authorId];

        // Update the book identifier
        if (!is_null($bookId) && !is_null($matchId)) {
            if (!$this->updateBookIdentifier('google', $bookId, $matchId)) {
                $this->addError($this->dbFileName, "Failed updating google identifier for bookId {$bookId} to {$matchId}");
                return null;
            }
        }

        // Find match on Google Books
        $googlematch = new GoogleBooksMatch($this->cacheDir, $lang);

        $matched = null;
        $dbPath = $this->dbConfig['db_path'];
        if (!empty($bookId)) {
            $books = $this->db->getBooks($bookId);
            $query = $books[$bookId]['title'];
            $matched = $googlematch->findWorksByTitle($query, $author);
            //$info = GoogleBooksMatch::import($dbPath, $matched);
        } else {
            $sort = $this->request->get('sort');
            $offset = $this->request->getId('offset');
            $books = $this->db->getBooksByAuthor($authorId, $sort, $offset);
            $matched = $googlematch->findWorksByAuthor($author);
            //$info = GoogleBooksMatch::import($dbPath, $matched);
        }

        $authorList = $this->getAuthorList();
        $titles = [];
        foreach ($books as $id => $book) {
            $titles[$book['title']] = $id;
        }
        // exact match only here - see calibre metadata plugins for more advanced features
        foreach ($matched['items'] as $match) {
            if (array_key_exists($match['volumeInfo']['title'], $titles)) {
                if (!empty($match['volumeInfo']['authors']) && in_array($author['name'], $match['volumeInfo']['authors'])) {
                    $id = $titles[$match['volumeInfo']['title']];
                    $books[$id]['identifiers'][] = ['id' => 0, 'book' => $id, 'type' => '* google', 'value' => $match['id'], 'url' => GoogleBooksMatch::link($match['id'])];
                    unset($titles[$match['volumeInfo']['title']]);
                }
            }
        }
        $langList = GoogleBooksMatch::getLanguages();

        // Return info
        return ['books' => $books, 'authorId' => $authorId, 'author' => $authors[$authorId], 'bookId' => $bookId, 'matched' => $matched, 'authors' => $authorList, 'lang' => $lang, 'langList' => $langList];
    }

    /**
     * Summary of gb_volume
     * @param string $volumeId
     * @param string $lang
     * @return array<mixed>
     */
    public function gb_volume($volumeId, $lang)
    {
        $volume = [];

        // Get volume on Google Books
        if (!empty($volumeId)) {
            $googlematch = new GoogleBooksMatch($this->cacheDir, $lang);
            $volume = $googlematch->getVolume($volumeId);
        }
        $langList = GoogleBooksMatch::getLanguages();

        // Return info
        return ['volume' => $volume, 'volumeId' => $volumeId, 'lang' => $lang, 'langList' => $langList];
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
                return null;
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
            //    $firstId = array_keys($matched)[0];
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
        return ['authors' => $authors, 'authorId' => $authorId, 'matched' => $matched['docs'], 'paging' => $paging];
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
            $authorId = array_keys($authors)[0];
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
            $olid = $openlibrary->findAuthorId($author);
            $matched = $openlibrary->findWorksByAuthorId($olid);
        }
        usort($matched['docs'], function ($a, $b) {
            return $b['edition_count'] <=> $a['edition_count'];
        });

        $authorList = $this->getAuthorList();
        $titles = [];
        foreach ($books as $id => $book) {
            $titles[$book['title']] = $id;
        }
        // exact match only here - see calibre metadata plugins for more advanced features
        foreach ($matched['docs'] as $match) {
            if (array_key_exists($match['title'], $titles)) {
                if (!empty($match['author_name']) && in_array($author['name'], $match['author_name'])) {
                    $id = $titles[$match['title']];
                    $value = str_replace('/works/', '', $match['key']);
                    $books[$id]['identifiers'][] = ['id' => 0, 'book' => $id, 'type' => '* olid', 'value' => $value, 'url' => OpenLibraryMatch::link($value)];
                    unset($titles[$match['title']]);
                }
            }
        }

        // Return info
        return ['books' => $books, 'authorId' => $authorId, 'author' => $authors[$authorId], 'bookId' => $bookId, 'matched' => $matched['docs'], 'authors' => $authorList];
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
        return ['work' => $work, 'workId' => $workId];
    }

    /**
     * Summary of notes
     * @param string|null $colName
     * @param int|null $itemId
     * @param bool $html
     * @return array<mixed>
     */
    public function notes($colName = null, $itemId = null, $html = false)
    {
        $notescount = $this->db->getNotesCount();
        $items = [];
        if (!empty($colName)) {
            if (!empty($itemId)) {
                $items = $this->db->getNotes($colName, [$itemId]);
                if ($html) {
                    $dbNum = $this->dbConfig['db_num'];
                    $endpoint = $this->request->getEndpoint();
                    $items[$itemId]['doc'] = str_replace('calres://', $endpoint . '/resource/' . $dbNum . '?hash=', $items[$itemId]['doc']);
                    $items[$itemId]['doc'] = str_replace('?placement=', '&placement=', $items[$itemId]['doc']);
                }
            } else {
                $items = $this->db->getNotes($colName);
            }
        }
        return ['notescount' => $notescount, 'colName' => $colName, 'itemId' => $itemId, 'items' => $items, 'html' => $html];
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
                return null;
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
        return ['authors' => $authors, 'authorId' => $authorId, 'matched' => $matched, 'paging' => $paging];
    }

    /**
     * Summary of gr_books
     * @param int|null $authorId
     * @param int|null $bookId
     * @param string|null $matchId
     * @return array<mixed>|null
     */
    public function gr_books($authorId, $bookId, $matchId)
    {
        $authors = $this->db->getAuthors($authorId);
        if (empty($authorId) && empty($bookId)) {
            //$this->addError($this->dbFileName, "Please specify authorId and/or bookId");
            //return null;
            $authorId = array_keys($authors)[0];
        }
        if (count($authors) < 1) {
            $this->addError($this->dbFileName, "Please specify a valid authorId");
            return null;
        }
        $author = $authors[$authorId];

        // Update the book identifier
        if (!is_null($bookId) && !is_null($matchId)) {
            $this->updateBookIdentifier('goodreads', $bookId, $matchId);
        }

        // Find match on OpenLibrary
        $goodreads = new GoodReadsMatch($this->cacheDir);

        $authId = $this->request->get('authId');
        $matched = null;
        if (!empty($bookId)) {
            $books = $this->db->getBooks($bookId);
            $query = $books[$bookId]['title'];
            // @todo find books by title with GoodReads?
            //$matched = $goodreads->findWorksByTitle($query, $author);
            // generic search returns 'docs' but author search returns 'entries'
            //$matched['entries'] ??= $matched['docs'];
        } else {
            $sort = $this->request->get('sort');
            $offset = $this->request->getId('offset');
            $books = $this->db->getBooksByAuthor($authorId, $sort, $offset);
        }
        if (!empty($matchId)) {
            $found = $goodreads->getBook($matchId);
            $dbPath = $this->dbConfig['db_path'];
            $info = GoodReadsMatch::import($dbPath, $found);
            $matched[] = [
                'id' => GoodReadsMatch::entity($info->mUri),
                'title' => $info->mTitle,
                'url' => $info->mUri,
                'cover' => $info->mCover,
                'series' => [
                    'id' => $info->mSerieId,
                    'title' => $info->mSerie,
                    'index' => $info->mSerieIndex,
                ],
            ];
        } elseif (!empty($authId)) {
            $found = $goodreads->getAuthor($authId);
            // remove books from other authors here?
            $matched = $found[$authId]['books'];
        } else {
            $olid = $goodreads->findAuthorId($author);
            $found = $goodreads->getAuthor($olid);
            // remove books from other authors here?
            $matched = $found[$olid]['books'];
        }

        $authorList = $this->getAuthorList();
        $titles = [];
        foreach ($books as $id => $book) {
            $titles[$book['title']] = $id;
        }
        // exact match only here - see calibre metadata plugins for more advanced features
        foreach ($matched as $key => $match) {
            $matched[$key]['key'] = $match['id'];
            $matched[$key]['id'] = GoodReadsMatch::bookid($match['id']);
            if (array_key_exists($match['title'], $titles)) {
                $id = $titles[$match['title']];
                $books[$id]['identifiers'][] = ['id' => 0, 'book' => $id, 'type' => '* goodreads', 'value' => $matched[$key]['id'], 'url' => GoodReadsMatch::link($match['id'])];
                unset($titles[$match['title']]);
            }
        }

        // Return info
        return ['books' => $books, 'authorId' => $authorId, 'author' => $authors[$authorId], 'bookId' => $bookId, 'matched' => $matched, 'authors' => $authorList, 'matchId' => $matchId];
    }

    /**
     * Summary of gr_series
     * @param int|null $authorId
     * @param int|null $seriesId
     * @param string|null $matchId
     * @return array<mixed>|null
     */
    public function gr_series($authorId, $seriesId, $matchId)
    {
        $authors = $this->db->getAuthors($authorId);
        if (empty($authorId) && empty($seriesId)) {
            //$this->addError($this->dbFileName, "Please specify authorId and/or seriesId");
            //return null;
            //$authorId = array_keys($authors)[0];
        }

        if (count($authors) < 1) {
            $this->addError($this->dbFileName, "Please specify a valid authorId");
            return null;
        }
        //$author = $authors[$authorId];
        $sort = $this->request->get('sort');
        $offset = $this->request->getId('offset');

        // Find match on GoodReads
        $goodreads = new GoodReadsMatch($this->cacheDir);
        //$entityId = $goodreads->findAuthorId($author);

        $matched = null;
        if (!empty($seriesId)) {
            $series = $this->db->getSeries($seriesId, $authorId, null, $sort, $offset);
            // series can have multiple authors
            $first = array_values($series)[0];
            // Update the series link
            if (!empty($matchId) && empty($first['link'])) {
                $link = GoodReadsMatch::SERIES_URL . $matchId;
                if (!$this->db->setSeriesLink($seriesId, $link)) {
                    $this->addError($this->dbFileName, "Failed updating link {$link} for seriesId {$seriesId}");
                    return null;
                }
            }
        } else {
            $series = $this->db->getSeriesByAuthor($authorId, $sort, $offset);
        }
        foreach ($series as $id => $serie) {
            if (!empty($serie['link']) && str_starts_with($serie['link'], GoodReadsMatch::SERIES_URL)) {
                $series[$id]['entityType'] = 'gr_series';
                $series[$id]['entityId'] = str_replace(GoodReadsMatch::SERIES_URL, '', $serie['link']);
                if (empty($matchId) && !empty($seriesId)) {
                    $matchId = $series[$id]['entityId'];
                }
            }
        }
        if (!empty($matchId)) {
            $found = $goodreads->getSeries($matchId);
            if (!empty($found)) {
                $dbPath = $this->dbConfig['db_path'];
                $info = GoodReadsMatch::import($dbPath, $found);
                $matched = [];
                $match = [
                    'id' => $matchId,
                    'title' => $info->getTitle(),
                    'count' => $info->getNumWorks(),
                    'description' => $info->getDescription(),
                    'link' => 'https://www.goodreads.com/series/' . $matchId,
                ];
                $matched[] = $match;
            }
        } elseif (empty($authorId)) {
            $matched = [];
            foreach ($goodreads->getSeriesIds() as $id) {
                $matched[] = [
                    'id' => $id,
                    'title' => '',
                    'count' => '',
                    'description' => '',
                    'link' => 'https://www.goodreads.com/series/' . $id,
                ];
            }
            if (count($matched) > $this->db->limit) {
                $matched = array_slice($matched, $offset, $this->db->limit);
            }
        }
        $paging = ($seriesId || $authorId) ? null : $this->db->getSeriesPaging($sort, $offset);

        $authorList = $this->getAuthorList();

        // Return info
        return ['series' => $series, 'authorId' => $authorId, 'author' => $authors[$authorId], 'seriesId' => $seriesId, 'matched' => $matched, 'authors' => $authorList, 'paging' => $paging];
    }

    /**
     * Summary of resource
     * @param string|null $hash
     * @return null
     */
    public function resource($hash = null)
    {
        if (empty($hash)) {
            $this->addError($this->dbFileName, "Please specify a resource hash");
            return null;
        }
        [$alg, $digest] = explode('/', $hash);
        $hash = "{$alg}-{$digest}";
        $path = $this->db->getResourcePath($hash);
        if (empty($path)) {
            $this->addError($this->dbFileName, "Please specify a valid resource hash");
            return null;
        }
        $meta = json_decode(file_get_contents($path . '.metadata'), true);
        $ext = strtolower(pathinfo((string) $meta['name'], PATHINFO_EXTENSION));
        $mime = 'application/octet-stream';
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $mime = 'image/jpeg';
                break;
            case 'png':
                $mime = 'image/png';
                break;
        }
        $expires = 60 * 60 * 24 * 14;
        header('Pragma: public');
        header('Cache-Control: max-age=' . $expires);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
        header('Content-Type: ' . $mime);

        readfile($path);
        if (!empty(getenv('PHPUNIT_TESTING'))) {
            return null;
        }
        exit;
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
                if (str_starts_with($author['link'], GoodReadsMatch::AUTHOR_URL)) {
                    $authors[$id]['entityType'] = 'gr_author';
                    $authors[$id]['entityId'] = GoodReadsMatch::entity($author['link']);
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
        $bookcount = $this->db->getBookCount($authorId);
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
        $seriescount = $this->db->getSeriesCount($authorId);
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
     * Summary of addError
     * @param string $file
     * @param mixed $message
     * @return void
     */
    public function addError($file, $message)
    {
        $this->gErrorArray[$file] = $message;
    }

    /**
     * Summary of getErrors
     * @return array<mixed>
     */
    public function getErrors()
    {
        return $this->gErrorArray;
    }

    /**
     * Summary of hasAction
     * @param string $action
     * @return bool
     */
    public static function hasAction($action)
    {
        if (method_exists(static::class, $action)) {
            return true;
        }
        return false;
    }
}
