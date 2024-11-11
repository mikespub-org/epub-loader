<?php
/**
 * CalibreReader class
 */

namespace Marsender\EPubLoader\Workflows\Readers;

use Marsender\EPubLoader\CalibreDbLoader;
use Marsender\EPubLoader\Workflows\Workflow;

class CalibreReader extends DatabaseReader
{
    /** @var CalibreDbLoader */
    protected $dbLoader;

    /**
     * Open a Calibre database file
     *
     * @param ?Workflow $workflow
     * @param string $dbFileName Calibre database file name
     */
    public function __construct($workflow, $dbFileName)
    {
        $this->dbFileName = $dbFileName;
        $this->dbLoader = new CalibreDbLoader($dbFileName);
        $this->dbLoader->getNotesDb();
        $this->setWorkflow($workflow);
    }

    /**
     * Load books from <something> in path
     *
     * @param string $basePath base directory
     * @param string $localPath relative to $basePath
     *
     * @return void
     */
    public function process($basePath, $localPath)
    {
        // @todo loop over database to load BookInfo and add books
        $message = 'TODO';
        $this->addMessage($localPath, $message);
    }
}
