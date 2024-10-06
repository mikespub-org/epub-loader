<?php
/**
 * CalibreExport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Export;

use Marsender\EPubLoader\Metadata\BookInfos;
use Exception;

class CalibreExport extends SourceExport
{
    /**
     * Load books from <something> in path
     *
     * @param string $inBasePath base directory
     * @param string $localPath relative to $inBasePath
     *
     * @return array{string, array<mixed>}
     */
    public function loadFromPath($inBasePath, $localPath)
    {
        // @todo loop over database to load BookInfos and add books
        $errors = [];
        $message = 'TODO';
        return [$message, $errors];
    }
}
