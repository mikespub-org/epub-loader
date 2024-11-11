<?php
/**
 * SourceReader class
 * |-> BookReader (LOCAL_BOOKS)
 * |-> CalibreReader (CALIBRE_DB)
 * |-> CsvFileReader (CSV_FILES)
 * |-> JsonFileReader (JSON_FILES)
 * |-> ...
 * implement process()
 */

namespace Marsender\EPubLoader\Workflows\Readers;

use Marsender\EPubLoader\Workflows\Workflow;

abstract class SourceReader
{
    /** @var ?Workflow */
    protected $workflow = null;
    /** @var array<mixed> */
    public array $messages = [];
    /** @var array<mixed> */
    public array $errors = [];

    /**
     * Initialize reader
     *
     * @param ?Workflow $workflow
     */
    public function __construct($workflow = null)
    {
        $this->setWorkflow($workflow);
    }

    /**
     * Set current workflow
     * @param ?Workflow $workflow
     * @return void
     */
    public function setWorkflow($workflow)
    {
        $this->workflow = $workflow;
    }

    /**
     * Load books from <something> in path
     *
     * @param string $basePath base directory
     * @param string $localPath relative to $basePath
     *
     * @return void
     */
    abstract public function process($basePath, $localPath);

    /**
     * Summary of addMessage
     * @param string $source
     * @param mixed $message
     * @return void
     */
    public function addMessage($source, $message)
    {
        $this->messages[$source] = $message;
    }

    /**
     * Summary of addError
     * @param string $source
     * @param mixed $error
     * @return void
     */
    public function addError($source, $error)
    {
        $this->errors[$source] = $error;
    }
}
