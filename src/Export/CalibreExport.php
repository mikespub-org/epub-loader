<?php
/**
 * CalibreExport class
 */

namespace Marsender\EPubLoader\Export;

use Marsender\EPubLoader\Models\BookInfo;
use Exception;

class CalibreExport extends SourceExport
{
    /**
     * Load books from <something> in path
     *
     * @param string $basePath base directory
     * @param string $localPath relative to $basePath
     *
     * @return array{string, array<mixed>}
     */
    public function loadFromPath($basePath, $localPath)
    {
        // @todo loop over database to load BookInfo and add books
        $errors = [];
        $message = 'TODO';
        return [$message, $errors];
    }
}
