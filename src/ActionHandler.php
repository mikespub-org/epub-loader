<?php
/**
 * ActionHandler class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

use Marsender\EPubLoader\Export\BookExport;
use Marsender\EPubLoader\Metadata\Sources\GoogleBooksMatch;
use Marsender\EPubLoader\Metadata\Sources\OpenLibraryMatch;
use Marsender\EPubLoader\Metadata\Sources\WikiDataMatch;

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
        switch($action) {
            case 'csv_export':
                $result = $this->csv_export();
                break;
            case 'db_load':
                $createDb = $this->dbConfig['create_db'];
                $result = $this->db_load($createDb);
                break;
            case 'authors':
                $result = $this->authors($authorId);
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
        $fileName = $dbPath . DIRECTORY_SEPARATOR . basename($dbPath) . '_metadata.csv';
        // Open or create the export file
        $export = new BookExport($fileName, BookExport::eExportTypeCsv, true);
        // Add the epub files into the export file
        $nbOk = 0;
        $nbError = 0;
        $epubPath = $this->dbConfig['epub_path'];
        if (!empty($epubPath)) {
            $fileList = RequestHandler::getFiles($dbPath . DIRECTORY_SEPARATOR . $epubPath, '*.epub');
            foreach ($fileList as $file) {
                $filePath = substr($file, strlen($dbPath) + 1);
                $error = $export->AddEpub($dbPath, $filePath);
                if (!empty($error)) {
                    $this->addError($file, $error);
                    $nbError++;
                    continue;
                }
                $nbOk++;
            }
        }
        // Save export
        $export->SaveToFile();
        // Display info
        return sprintf('Export ebooks to %s - %d files OK - %d files Error', $fileName, $nbOk, $nbError) . '<br />';
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
        $db = new CalibreDbLoader($calibreFileName, $createDb, $bookIdsFileName);
        // Add the epub files into the database
        $nbOk = 0;
        $nbError = 0;
        $epubPath = $this->dbConfig['epub_path'];
        if (!empty($epubPath)) {
            $fileList = RequestHandler::getFiles($dbPath . DIRECTORY_SEPARATOR . $epubPath, '*.epub');
            foreach ($fileList as $file) {
                $filePath = substr($file, strlen($dbPath) + 1);
                $error = $db->AddEpub($dbPath, $filePath);
                if (!empty($error)) {
                    $this->addError($file, $error);
                    $nbError++;
                    continue;
                }
                $nbOk++;
            }
        }
        // Display info
        return sprintf('Load database %s - %d files OK - %d files Error', $calibreFileName, $nbOk, $nbError) . '<br />';
    }

    /**
     * Summary of authors
     * @param int|null $authorId
     * @return array<mixed>|null
     */
    public function authors($authorId = null)
    {
        // List the authors
        $authors = $this->db->getAuthors($authorId);
        $matched = null;
        $authors = $this->addAuthorLinks($authors);
        $bookcount = $this->db->getBookCount();
        $seriescount = $this->db->getSeriesCount();
        // Return info
        return ['authors' => $authors, 'authorId' => $authorId, 'matched' => $matched, 'bookcount' => $bookcount, 'seriescount' => $seriescount];
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

        // List the authors
        $authors = $this->db->getAuthors($authorId);
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
        $authors = $this->addAuthorLinks($authors);
        $bookcount = $this->db->getBookCount();
        $seriescount = $this->db->getSeriesCount();

        // Return info
        return ['authors' => $authors, 'authorId' => $authorId, 'matched' => $matched, 'bookcount' => $bookcount, 'seriescount' => $seriescount];
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
            $books = $this->db->getBooksByAuthor($authorId);
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
                $books[$id]['identifiers'][] = ['id' => 0, 'book' => $id, 'type' => '* wd', 'value' => $match['id']];
                unset($titles[$match['title']]);
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
            $query = $series[$seriesId]['name'];
            $matched = $wikimatch->findSeriesByName($query);
        } else {
            $series = $this->db->getSeriesByAuthor($authorId);
            if (count($series) > 0) {
                $matched = $wikimatch->findSeriesByAuthor($author);
            }
        }

        $authorList = $this->getAuthorList();

        // Return info
        return ['series' => $series, 'authorId' => $authorId, 'author' => $authors[$authorId], 'seriesId' => $seriesId, 'matched' => $matched, 'authors' => $authorList];
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
        if (!empty($bookId)) {
            $books = $this->db->getBooks($bookId);
            $query = $books[$bookId]['title'];
            $matched = $googlematch->findWorksByTitle($query, $author);
        } else {
            $books = $this->db->getBooksByAuthor($authorId);
            $matched = $googlematch->findWorksByAuthor($author);
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
                    $books[$id]['identifiers'][] = ['id' => 0, 'book' => $id, 'type' => '* google', 'value' => $match['id']];
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

        // List the authors
        $authors = $this->db->getAuthors($authorId);
        $author = null;
        $query = null;
        if (!is_null($authorId) && is_null($matchId)) {
            $author = $authors[$authorId];
            $query = $author['name'];
        }

        // Find match on OpenLibrary
        $openlibrary = new OpenLibraryMatch($this->cacheDir);

        $matched = null;
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
            $matched = ['docs' => []];
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
        $authors = $this->addAuthorLinks($authors);
        $bookcount = $this->db->getBookCount();
        $seriescount = $this->db->getSeriesCount();

        // Return info
        return ['authors' => $authors, 'authorId' => $authorId, 'matched' => $matched['docs'], 'bookcount' => $bookcount, 'seriescount' => $seriescount];
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

        $matched = null;
        if (!empty($bookId)) {
            $books = $this->db->getBooks($bookId);
            $query = $books[$bookId]['title'];
            $matched = $openlibrary->findWorksByTitle($query, $author);
            // generic search returns 'docs' but author search returns 'entries'
            //$matched['entries'] ??= $matched['docs'];
        } elseif (!empty($matchId)) {
            $books = $this->db->getBooksByAuthor($authorId);
            $matched = $openlibrary->findWorksByAuthorId($matchId);
        } else {
            $books = $this->db->getBooksByAuthor($authorId);
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
                    $books[$id]['identifiers'][] = ['id' => 0, 'book' => $id, 'type' => '* olid', 'value' => str_replace('/works/', '', $match['key'])];
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
        $authorList = [];
        $authors = $this->db->getAuthors();
        foreach ($authors as $authorId => $author) {
            $authorList[$authorId] = $author['name'];
        }
        return $authorList;
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
                }
                if (OpenLibraryMatch::isValidLink($author['link'])) {
                    $authors[$id]['entityType'] = 'ol_work';
                    $authors[$id]['entityId'] = OpenLibraryMatch::entity($author['link']);
                }
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
