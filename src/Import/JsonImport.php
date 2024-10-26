<?php
/**
 * JsonImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\BaseCache;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsCache;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsImport;
use Marsender\EPubLoader\Metadata\GoogleBooks\GoogleBooksCache;
use Marsender\EPubLoader\Metadata\GoogleBooks\GoogleBooksImport;
use Marsender\EPubLoader\Metadata\OpenLibrary\OpenLibraryCache;
use Marsender\EPubLoader\Metadata\OpenLibrary\OpenLibraryImport;
use Marsender\EPubLoader\Metadata\WikiData\WikiDataCache;
use Marsender\EPubLoader\Metadata\WikiData\WikiDataImport;
use Exception;

class JsonImport extends SourceImport
{
    /** @var array<mixed> */
    protected array $caches = [];

    /**
     * Load books from JSON file
     * @param string $basePath base directory
     * @param string $fileName
     * @return array{string, array<mixed>}
     */
    public function loadFromJsonFile($basePath, $fileName)
    {
        $content = file_get_contents($fileName);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $errors = [];
        $nbOk = 0;
        $nbError = 0;
        if (!empty($data["kind"]) && $data["kind"] == "books#volumes") {
            $this->caches['googlebooks'] ??= new GoogleBooksCache($this->cacheDir);
            // Parse the JSON data
            $result = GoogleBooksCache::parseSearch($data);
            if (empty($result->getItems())) {
                $result->items = [];
            }
            foreach ($result->getItems() as $volume) {
                try {
                    // Load the book infos
                    $bookInfo = GoogleBooksImport::load($basePath, $volume, $this->caches['googlebooks']);
                    // Add the book
                    $this->addBook($bookInfo, 0);
                    $nbOk++;
                } catch (Exception $e) {
                    $id = $volume->getId() ?? spl_object_hash($volume);
                    $errors[$id] = $e->getMessage();
                    $nbError++;
                }
            }
        } elseif (!empty($data["kind"]) && $data["kind"] == "books#volume") {
            $this->caches['googlebooks'] ??= new GoogleBooksCache($this->cacheDir);
            try {
                // Parse the JSON data
                $volume = GoogleBooksCache::parseVolume($data);
                // Load the book infos
                $bookInfo = GoogleBooksImport::load($basePath, $volume, $this->caches['googlebooks']);
                // Add the book
                $this->addBook($bookInfo, 0);
                $nbOk++;
            } catch (Exception $e) {
                $id = basename($fileName);
                $errors[$id] = $e->getMessage();
                $nbError++;
            }
        } elseif (!empty($data["page"]) && $data["page"] == "/book/show/[book_id]") {
            $this->caches['goodreads'] ??= new GoodReadsCache($this->cacheDir);
            try {
                // Parse the JSON data
                $bookResult = GoodReadsCache::parseBook($data);
                // Load the book infos
                $bookInfo = GoodReadsImport::load($basePath, $bookResult, $this->caches['goodreads']);
                // Add the book
                $this->addBook($bookInfo, 0);
                $nbOk++;
            } catch (Exception $e) {
                $id = basename($fileName);
                $errors[$id] = $e->getMessage();
                $nbError++;
            }
        } elseif (!empty($data["type"]) && !empty($data["type"]["key"]) && $data["type"]["key"] == "/type/work") {
            $this->caches['openlibrary'] ??= new OpenLibraryCache($this->cacheDir);
            try {
                // Parse the JSON data
                $work = OpenLibraryCache::parseWorkEntity($data);
                // Load the book infos
                $bookInfo = OpenLibraryImport::load($basePath, $work, $this->caches['openlibrary']);
                // Add the book
                $this->addBook($bookInfo, 0);
                $nbOk++;
            } catch (Exception $e) {
                $id = basename($fileName);
                $errors[$id] = $e->getMessage();
                $nbError++;
            }
        } elseif (!empty($data["type"]) && !empty($data["type"]["key"]) && $data["type"]["key"] == "/type/author") {
            $this->caches['openlibrary'] ??= new OpenLibraryCache($this->cacheDir);
            // not imported separately
            try {
                // Parse the JSON data
                $author = OpenLibraryCache::parseAuthorEntity($data);
                //$nbOk++;
            } catch (Exception $e) {
                $id = basename($fileName);
                $errors[$id] = $e->getMessage();
                $nbError++;
            }
        } elseif (!empty($data["id"]) && !empty($data["properties"]) && array_key_exists("wiki_url", $data)) {
            $this->caches['wikidata'] ??= new WikiDataCache($this->cacheDir);
            try {
                // Parse the JSON data
                $entity = WikiDataCache::parseEntity($data);
                if (!empty($entity) && $entity['type'] == 'book') {
                    // Load the book infos
                    $bookInfo = WikiDataImport::load($basePath, $entity, $this->caches['wikidata']);
                    // Add the book
                    $this->addBook($bookInfo, 0);
                    $nbOk++;
                } else {
                    // not imported separately
                }
            } catch (Exception $e) {
                $id = basename($fileName);
                $errors[$id] = $e->getMessage();
                $nbError++;
            }
        } else {
            // @todo add more formats to support
        }
        $message = sprintf('Import ebooks from %s - %d files OK - %d files Error', $fileName, $nbOk, $nbError);
        return [$message, $errors];
    }

    /**
     * Load books from JSON files in path
     *
     * @param string $basePath base directory
     * @param string $jsonPath relative to $basePath
     *
     * @return array{string, array<mixed>}
     */
    public function loadFromPath($basePath, $jsonPath)
    {
        $allErrors = [];
        $allMessages = '';
        $fileList = BaseCache::getFiles($basePath . DIRECTORY_SEPARATOR . $jsonPath, '*.json');
        foreach ($fileList as $file) {
            [$message, $errors] = $this->loadFromJsonFile($basePath, $file);
            $allMessages .= $message . '<br />';
            $allErrors = array_merge($allErrors, $errors);
            //$allMessages = $message;
            //$allErrors = $errors;
        }
        return [$allMessages, $allErrors];
    }
}
