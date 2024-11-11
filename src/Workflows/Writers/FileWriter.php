<?php
/**
 * FileWriter class
 */

namespace Marsender\EPubLoader\Workflows\Writers;

use Exception;

abstract class FileWriter extends TargetWriter
{
    /** @var array<mixed>|null */
    protected $properties = null;
    protected string $fileName = '';
    /** @var array<mixed>|null */
    protected $search = null;
    /** @var array<mixed>|null */
    protected $replace = null;

    public bool $formatProperty = true;

    /**
     * Open an export file (or create if file does not exist)
     *
     * @param string $fileName Export file name
     * @param boolean $create Force file creation
     */
    public function __construct($fileName, $create = false)
    {
        if ($create && file_exists($fileName)) {
            if (!unlink($fileName)) {
                $error = sprintf('Cannot remove file: %s', $fileName);
                throw new Exception($error);
            }
        }

        $this->fileName = $fileName;

        $this->properties = [];
    }

    /**
     * Summary of clearProperties
     * @return void
     */
    public function clearProperties()
    {
        $this->properties = [];
    }

    /**
     * Summary of SetProperty
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function setProperty($key, $value)
    {
        // Don't store empty keys
        if (empty($key)) {
            return;
        }

        if ($this->formatProperty) {
            $value = $this->formatProperty($value);
        }

        $this->properties[$key] = $value;
    }

    /**
     * Format a property
     *
     * @param string|array<mixed>|null $value of strings to format
     * @return string|array<mixed> of strings formated
     */
    protected function formatProperty($value)
    {
        if (!isset($value)) {
            return '';
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            // Recursive call for arrays
            foreach ($value as $key => $val) {
                $value[$key] = $this->formatProperty($val);
            }
            return $value;
        }
        if (!is_string($value) || empty($value)) {
            return '';
        }

        // Replace html entities with normal characters
        $str = html_entity_decode($value, ENT_COMPAT, 'UTF-8');
        // Replace characters
        if (isset($this->search)) {
            $str = str_replace($this->search, $this->replace, $str);
        }

        // Strip double spaces
        while (str_contains($str, '  ')) {
            $str = str_replace('  ', ' ', $str);
        }

        // Trim
        $str = trim($str);

        return $str;
    }

    /**
     * Save data to file
     *
     * @throws Exception if error
     * @return void
     */
    public function saveToFile()
    {
        // Write the file
        $content = $this->getContent();
        if (!file_put_contents($this->fileName, $content)) {
            $error = sprintf('Cannot save export to file: %s', $this->fileName);
            throw new Exception($error);
        }
    }

    /**
     * Send download http headers
     *
     * @param string $fileName Download file name to display in the browser
     * @param int $fileSize Download file size
     * @param string $codeSet Charset
     * @throws exception if http headers have been already sent
     *
     * @return void
     */
    protected function sendDownloadHeaders($fileName, $fileSize = null, $codeSet = 'utf-8')
    {
        // Throws excemtion if http headers have been already sent
        $filename = '';
        $linenum = 0;
        if (headers_sent($filename, $linenum)) {
            $error = sprintf('Http headers already sent by file: %s line %d', $filename, $linenum);
            throw new Exception($error);
        }

        $fileName = str_replace(' ', '', basename($fileName)); // Cleanup file name
        $ext = strtolower(substr(strrchr($fileName, '.'), 1));

        switch ($ext) {
            case 'pdf':
                $contentType = 'application/pdf';
                break;
            case 'zip':
                $contentType = 'application/zip';
                break;
            case 'xml':
                $contentType = 'text/xml';
                if (!empty($codeSet)) {
                    $contentType .= '; charset=' . $codeSet;
                }
                break;
            case 'txt':
                $contentType = 'text/plain';
                if (!empty($codeSet)) {
                    $contentType .= '; charset=' . $codeSet;
                }
                break;
            case 'csv':
                $contentType = 'text/csv';
                if (!empty($codeSet)) {
                    $contentType .= '; charset=' . $codeSet;
                }
                break;
            case 'json':
                $contentType = 'application/json';
                if (!empty($codeSet)) {
                    $contentType .= '; charset=' . $codeSet;
                }
                break;
            case 'html':
                $contentType = 'text/html';
                if (!empty($codeSet)) {
                    $contentType .= '; charset=' . $codeSet;
                }
                break;
            default:
                $contentType = 'application/force-download';
                break;
        }

        // Send http headers for download
        header('Content-disposition: attachment; filename="' . $fileName . '"');
        Header('Content-Type: ' . $contentType);
        //header('Content-Transfer-Encoding: binary');
        if (isset($fileSize)) {
            header('Content-Length: ' . $fileSize);
        }

        // Send http headers to remove the browser cache
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    /**
     * Download export and stop further script execution
     * @return void
     */
    public function download()
    {
        $content = $this->getContent();

        // Send http download headers
        $size = strlen($content);
        $this->sendDownloadHeaders($this->fileName, $size);

        // Send file content to download
        echo $content;

        if (!empty(getenv('PHPUNIT_TESTING'))) {
            return;
        }
        exit;
    }

    /**
     * Summary of getContent
     * @return string
     */
    protected function getContent()
    {
        return '';
    }
}
