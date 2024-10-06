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
     * @param string $inBasePath base directory
     * @param string $epubPath relative to $inBasePath
     *
     * @return array{string, array<mixed>}
     */
    public function loadFromPath($inBasePath, $epubPath)
    {
        $errors = [];
        $nbOk = 0;
        $nbError = 0;
        if (!empty($epubPath)) {
            $fileList = BaseCache::getFiles($inBasePath . DIRECTORY_SEPARATOR . $epubPath, '*.epub');
            foreach ($fileList as $file) {
                $filePath = substr($file, strlen((string) $inBasePath) + 1);
                try {
                    // Load the book infos
                    $bookInfos = LocalBooksImport::load($inBasePath, $filePath);
                    // Add the book
                    $bookId = $this->getBookId($filePath);
                    $this->addBook($bookInfos, $bookId);
                    $nbOk++;
                } catch (Exception $e) {
                    $errors[$file] = $e->getMessage();
                    $nbError++;
                }
            }
        }
        $message = sprintf('%s %s - %d files OK - %d files Error', $this->mLabel, $this->mFileName, $nbOk, $nbError);
        return [$message, $errors];
    }
}
