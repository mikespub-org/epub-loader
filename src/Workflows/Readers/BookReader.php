<?php
/**
 * BookReader class
 */

namespace Marsender\EPubLoader\Workflows\Readers;

use Marsender\EPubLoader\Metadata\BaseCache;
use Marsender\EPubLoader\Metadata\LocalBooks\LocalBooksImport;
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Exception;

class BookReader extends SourceReader
{
    /**
     * Load books from epub files in path
     *
     * @param string $basePath base directory
     * @param string $epubPath relative to $basePath
     *
     * @return \Generator<int, BookInfo|AuthorInfo|SeriesInfo>
     */
    public function iterate($basePath, $epubPath)
    {
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
                    yield 0 => $bookInfo;
                    //$bookId = $this->workflow->getId('books', $filePath);
                    //$this->workflow->addBook($bookInfo, $bookId);
                    $nbOk++;
                } catch (Exception $e) {
                    $this->addError($file, $e->getMessage());
                    $nbError++;
                }
            }
        }
        $message = sprintf('Load EPUB from %s - %d files OK - %d files Error', $basePath . DIRECTORY_SEPARATOR . $epubPath, $nbOk, $nbError);
        $this->addMessage($basePath, $message);
    }
}
