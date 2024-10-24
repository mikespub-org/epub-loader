<?php
/**
 * LocalBooksTrait for use in BookExport and BookImport
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\LocalBooks;

use Marsender\EPubLoader\Metadata\BaseCache;
use Exception;

trait LocalBooksTrait
{
    /**
     * Load books from epub files in path
     *
     * @param string $basePath base directory
     * @param string $epubPath relative to $basePath
     *
     * @return array{string, array<mixed>}
     */
    public function loadFromPath($basePath, $epubPath)
    {
        $errors = [];
        $nbOk = 0;
        $nbError = 0;
        if (!empty($epubPath)) {
            $fileList = BaseCache::getFiles($basePath . DIRECTORY_SEPARATOR . $epubPath, '*.epub');
            foreach ($fileList as $file) {
                $filePath = substr($file, strlen((string) $basePath) + 1);
                try {
                    // Load the book infos
                    $bookInfo = LocalBooksImport::load($basePath, $filePath);
                    // Add the book
                    $bookId = $this->getBookId($filePath);
                    $this->addBook($bookInfo, $bookId);
                    $nbOk++;
                } catch (Exception $e) {
                    $errors[$file] = $e->getMessage();
                    $nbError++;
                }
            }
        }
        $message = sprintf('%s %s - %d files OK - %d files Error', $this->label, $this->fileName, $nbOk, $nbError);
        return [$message, $errors];
    }
}
