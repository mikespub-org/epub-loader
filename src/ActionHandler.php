<?php
/**
 * ActionHandler class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

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
     * @return mixed
     */
    public function handle($action)
    {
        $authorId = isset($_GET['authorId']) ? (int)$_GET['authorId'] : null;
        $matchId = $_GET['matchId'] ?? null;
        switch($action) {
            case 'csv_export':
                $result = $this->csv_export();
                break;
            case 'db_load':
                $createDb = $this->dbConfig['create_db'];
                $result = $this->db_load($createDb);
                break;
            case 'authors':
                if (!empty($matchId) && !preg_match('/^Q\d+$/', $matchId)) {
                    $matchId = null;
                }
                $result = $this->authors($authorId, $matchId);
                break;
            case 'books':
                $bookId = isset($_GET['bookId']) ? (int)$_GET['bookId'] : null;
                if (!empty($matchId) && !preg_match('/^Q\d+$/', $matchId)) {
                    $matchId = null;
                }
                $result = $this->books($authorId, $bookId, $matchId);
                break;
            case 'series':
                $seriesId = isset($_GET['seriesId']) ? (int)$_GET['seriesId'] : null;
                if (!empty($matchId) && !preg_match('/^Q\d+$/', $matchId)) {
                    $matchId = null;
                }
                $result = $this->series($authorId, $seriesId, $matchId);
                break;
            case 'wikidata':
                if (!empty($matchId) && !preg_match('/^Q\d+$/', $matchId)) {
                    $matchId = null;
                }
                $result = $this->wikidata($matchId, $authorId);
                break;
            case 'google':
                $bookId = isset($_GET['bookId']) ? (int)$_GET['bookId'] : null;
                $lang = $_GET['lang'] ?? 'en';
                $result = $this->google($authorId, $bookId, $matchId, $lang);
                break;
            case 'volume':
                $lang = $_GET['lang'] ?? 'en';
                $result = $this->volume($matchId, $lang);
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
     * @param string|null $matchId
     * @return array<mixed>|null
     */
    public function authors($authorId, $matchId)
    {
        // Update the author link
        if (!is_null($authorId) && !is_null($matchId)) {
            $link = WikiMatch::link($matchId);
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
        $matched = null;
        if (!is_null($query)) {
            // Find match on Wikidata
            $wikimatch = new WikiMatch($this->cacheDir);
            $matched = $wikimatch->findAuthors($query);
            // Find works from author for 1st match
            if (count($matched) > 0) {
                $firstId = array_keys($matched)[0];
                $matched[$firstId]['entries'] = $wikimatch->findWorksByAuthor($author);
            }
            // https://www.googleapis.com/books/v1/volumes?q=inauthor:%22Anne+Bishop%22&langRestrict=en&startIndex=0&maxResults=40
        }
        foreach ($authors as $id => $author) {
            if (!empty($author['link'])) {
                $authors[$id]['entityId'] = WikiMatch::entity($author['link']);
            }
        }
        // Return info
        return ['authors' => $authors, 'authorId' => $authorId, 'matched' => $matched];
    }

    /**
     * Summary of books
     * @param int|null $authorId
     * @param int|null $bookId
     * @param string|null $matchId
     * @return array<mixed>|null
     */
    public function books($authorId, $bookId, $matchId)
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
        $wikimatch = new WikiMatch($this->cacheDir);
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
            $matched = $wikimatch->findWorksByAuthor($author);
            //$matched = array_merge($matched, $wikimatch->findWorksByName($author));
        }
        $authorList = $this->getAuthorList();

        // Return info
        return ['books' => $books, 'authorId' => $authorId, 'author' => $authors[$authorId], 'bookId' => $bookId, 'matched' => $matched, 'authors' => $authorList];
    }

    /**
     * Summary of series
     * @param int|null $authorId
     * @param int|null $seriesId
     * @param string|null $matchId
     * @return array<mixed>|null
     */
    public function series($authorId, $seriesId, $matchId)
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
        $wikimatch = new WikiMatch($this->cacheDir);
        //$entityId = $wikimatch->findAuthorId($author);

        $matched = null;
        if (!empty($seriesId)) {
            $series = $this->db->getSeries($seriesId);
            $query = $series[$seriesId]['title'];
            $matched = $wikimatch->findSeriesByTitle($query);
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
     * Summary of wikidata
     * @param string|null $entityId
     * @param int|null $authorId
     * @param string|null $query
     * @return array<mixed>
     */
    public function wikidata($entityId = null, $authorId = null, $query = null)
    {
        $entity = [];
        // Get entity on Wikidata
        if (!empty($authorId) && empty($entityId)) {
            $authors = $this->db->getAuthors($authorId);
            $author = $authors[$authorId];
            $wikimatch = new WikiMatch($this->cacheDir);
            $entityId = $wikimatch->findAuthorId($author);
        }
        if (!empty($entityId)) {
            $wikimatch = new WikiMatch($this->cacheDir);
            $entity = $wikimatch->getEntity($entityId);
        }
        $authorList = $this->getAuthorList();

        // Return info
        return ['entity' => $entity, 'entityId' => $entityId, 'authorId' => $authorId, 'authors' => $authorList];
    }

    /**
     * Summary of google
     * @param int|null $authorId
     * @param int|null $bookId
     * @param string|null $matchId
     * @param string $lang
     * @return array<mixed>|null
     */
    public function google($authorId, $bookId, $matchId, $lang = 'en')
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
        $googlematch = new GoogleMatch($this->cacheDir, $lang);

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

        // Return info
        return ['books' => $books, 'authorId' => $authorId, 'author' => $authors[$authorId], 'bookId' => $bookId, 'matched' => $matched, 'authors' => $authorList, 'lang' => $lang, 'langList' => GoogleMatch::getLanguages()];
    }

    /**
     * Summary of volume
     * @param string $volumeId
     * @param string $lang
     * @return array<mixed>
     */
    public function volume($volumeId, $lang)
    {
        $volume = [];

        // Get volume on Google Books
        if (!empty($volumeId)) {
            $googlematch = new GoogleMatch($this->cacheDir, $lang);
            $volume = $googlematch->getVolume($volumeId);
        }

        // Return info
        return ['volume' => $volume, 'volumeId' => $volumeId, 'lang' => $lang, 'langList' => GoogleMatch::getLanguages()];
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
