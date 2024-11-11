<?php
/**
 * Import class
 */

namespace Marsender\EPubLoader\Workflows;

use Exception;

class Import extends Workflow
{
    protected string $label = 'Load database';
    protected int $nbBook = 0;
    protected string $fileName = '';
    protected string $cacheDir = '';

    /**
     * Open an import database (or create if file does not exist)
     *
     * @param int    $sourceType Source type
     * @param string $dbFileName Calibre database file name
     * @param bool   $create Force database creation
     * @param string $bookIdsFileName File name containing a map of file names to calibre book ids
     * @param string|null $cacheDir
     * @throws Exception if error
     */
    public function __construct($sourceType, $dbFileName, $create = false, $bookIdsFileName = '', $cacheDir = null)
    {
        $this->fileName = $dbFileName;
        $this->cacheDir = $cacheDir ?? dirname(__DIR__, 2) . '/cache';
        $reader = Workflow::getReader($this, $sourceType, $this->cacheDir);
        // @todo support other import targets beside Calibre?
        $targetType = Workflow::CALIBRE_DB;
        $writer = Workflow::getWriter($this, $targetType, $dbFileName, $create);
        parent::__construct($reader, $writer, null, $bookIdsFileName);
    }
}
