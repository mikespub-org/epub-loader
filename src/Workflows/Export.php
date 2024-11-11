<?php
/**
 * Export class
 */

namespace Marsender\EPubLoader\Workflows;

use Exception;

class Export extends Workflow
{
    protected string $label = 'Export ebooks to';
    protected int $nbBook = 0;
    protected string $fileName = '';

    /**
     * Open an export file (or create if file does not exist)
     *
     * @param int    $sourceType Source type
     * @param string $sourcePath Source path
     * @param int    $targetType Target type
     * @param string $targetPath Target path
     * @param bool   $create Force creation
     * @throws Exception if error
     */
    public function __construct($sourceType, $sourcePath, $targetType, $targetPath, $create = false)
    {
        $this->reader = Workflow::getReader($this, $sourceType, $sourcePath);
        $this->writer = Workflow::getWriter($this, $targetType, $targetPath, $create);
        $this->fileName = $targetPath;
    }

    /**
     * Download export and stop further script execution
     * @return void
     */
    public function download()
    {
        if ($this->writer instanceof Writers\FileWriter) {
            $this->writer->download();
        }
    }

    /**
     * Save export to file
     * @return void
     */
    public function saveToFile()
    {
        if ($this->writer instanceof Writers\FileWriter) {
            $this->writer->saveToFile();
        }
    }
}
