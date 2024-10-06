<?php
/**
 * JsonImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Exception;

class JsonImport extends BookImport
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
            $result = GoogleBooksVolume::parseResult($data);
            if (empty($result->getItems())) {
                $result->items = [];
            }
            foreach ($result->getItems() as $volume) {
                try {
                    // Load the book infos
                    $bookInfos = GoogleBooksVolume::load($inBasePath, $volume);
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
                $volume = GoogleBooksVolume::parse($data);
                // Load the book infos
                $bookInfos = GoogleBooksVolume::load($inBasePath, $volume);
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
                $bookResult = GoodReadsBook::parse($data);
                // Load the book infos
                $bookInfos = GoodReadsBook::load($inBasePath, $bookResult);
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
                $work = OpenLibraryWork::parse($data);
                // Load the book infos
                $bookInfos = OpenLibraryWork::load($inBasePath, $work);
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
                $author = OpenLibraryWork::parseAuthor($data);
                //$nbOk++;
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
        $fileList = BaseImport::getFiles($inBasePath . DIRECTORY_SEPARATOR . $jsonPath, '*.json');
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
