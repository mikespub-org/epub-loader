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
    /**
     * Load books from JSON file
     * @param string $inBasePath base directory
     * @param string $fileName
     * @return array{string, array<mixed>}
     */
    public function loadFromJsonFile($inBasePath, $fileName)
    {
        $content = file_get_contents($fileName);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $errors = [];
        $nbOk = 0;
        $nbError = 0;
        if (!empty($data["kind"]) && $data["kind"] == "books#volumes") {
            // Parse the JSON data
            $result = GoogleBooksCache::parseSearch($data);
            if (empty($result->getItems())) {
                $result->items = [];
            }
            foreach ($result->getItems() as $volume) {
                try {
                    // Load the book infos
                    $bookInfos = GoogleBooksImport::load($inBasePath, $volume);
                    // Add the book
                    $this->addBook($bookInfos, 0);
                    $nbOk++;
                } catch (Exception $e) {
                    $id = $volume->getId() ?? spl_object_hash($volume);
                    $errors[$id] = $e->getMessage();
                    $nbError++;
                }
            }
        } elseif (!empty($data["kind"]) && $data["kind"] == "books#volume") {
            try {
                // Parse the JSON data
                $volume = GoogleBooksCache::parseVolume($data);
                // Load the book infos
                $bookInfos = GoogleBooksImport::load($inBasePath, $volume);
                // Add the book
                $this->addBook($bookInfos, 0);
                $nbOk++;
            } catch (Exception $e) {
                $id = basename($fileName);
                $errors[$id] = $e->getMessage();
                $nbError++;
            }
        } elseif (!empty($data["page"]) && $data["page"] == "/book/show/[book_id]") {
            try {
                // Parse the JSON data
                $bookResult = GoodReadsCache::parseBook($data);
                // Load the book infos
                $bookInfos = GoodReadsImport::load($inBasePath, $bookResult);
                // Add the book
                $this->addBook($bookInfos, 0);
                $nbOk++;
            } catch (Exception $e) {
                $id = basename($fileName);
                $errors[$id] = $e->getMessage();
                $nbError++;
            }
        } elseif (!empty($data["type"]) && !empty($data["type"]["key"]) && $data["type"]["key"] == "/type/work") {
            try {
                // Parse the JSON data
                $work = OpenLibraryCache::parseWorkEntity($data);
                // Load the book infos
                $bookInfos = OpenLibraryImport::load($inBasePath, $work);
                // Add the book
                $this->addBook($bookInfos, 0);
                $nbOk++;
            } catch (Exception $e) {
                $id = basename($fileName);
                $errors[$id] = $e->getMessage();
                $nbError++;
            }
        } elseif (!empty($data["type"]) && !empty($data["type"]["key"]) && $data["type"]["key"] == "/type/author") {
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
            try {
                // Parse the JSON data
                $entity = WikiDataCache::parseEntity($data);
                if (!empty($entity) && $entity['type'] == 'book') {
                    // Load the book infos
                    $bookInfos = WikiDataImport::load($inBasePath, $entity);
                    // Add the book
                    $this->addBook($bookInfos, 0);
                    $nbOk++;
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
     * @param string $inBasePath base directory
     * @param string $jsonPath relative to $inBasePath
     *
     * @return array{string, array<mixed>}
     */
    public function loadFromPath($inBasePath, $jsonPath)
    {
        $allErrors = [];
        $allMessages = '';
        $fileList = BaseCache::getFiles($inBasePath . DIRECTORY_SEPARATOR . $jsonPath, '*.json');
        foreach ($fileList as $file) {
            [$message, $errors] = $this->loadFromJsonFile($inBasePath, $file);
            $allMessages .= $message . '<br />';
            $allErrors = array_merge($allErrors, $errors);
            //$allMessages = $message;
            //$allErrors = $errors;
        }
        return [$allMessages, $allErrors];
    }
}
