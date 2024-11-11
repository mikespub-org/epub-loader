<?php
/**
 * DatabaseReader class
 */

namespace Marsender\EPubLoader\Workflows\Readers;

use Marsender\EPubLoader\DatabaseLoader;
use Marsender\EPubLoader\Workflows\Workflow;

abstract class DatabaseReader extends SourceReader
{
    /** @var DatabaseLoader */
    protected $dbLoader;
    protected string $dbFileName;

    /**
     * Open a database file
     *
     * @param ?Workflow $workflow
     * @param string $dbFileName database file name
     */
    public function __construct($workflow, $dbFileName)
    {
        $this->dbFileName = $dbFileName;
        $this->dbLoader = new DatabaseLoader($dbFileName);
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
