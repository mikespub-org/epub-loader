<?php
/**
 * ExportTarget class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Export;

use Marsender\EPubLoader\Metadata\BookInfos;
use Exception;

abstract class ExportTarget
{
    /** @var array<mixed>|null */
    protected $mProperties = null;
    protected string $mFileName = '';
    /** @var array<mixed>|null */
    protected $mSearch = null;
    /** @var array<mixed>|null */
    protected $mReplace = null;

    public bool $mFormatProperty = true;

    /**
     * Open an export file (or create if file does not exist)
     *
     * @param string $inFileName Export file name
     * @param boolean $inCreate Force file creation
     */
    public function __construct($inFileName, $inCreate = false)
    {
        if ($inCreate && file_exists($inFileName)) {
            if (!unlink($inFileName)) {
                $error = sprintf('Cannot remove file: %s', $inFileName);
                throw new Exception($error);
            }
        }

        $this->mFileName = $inFileName;

        $this->mProperties = [];
    }

    /**
     * Add a new book to the export
     *
     * @param BookInfos $inBookInfo BookInfo object
     * @param int $inBookId Book id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     *
     * @return void
     */
    abstract public function addBook($inBookInfo, $inBookId = 0);

    /**
     * Summary of clearProperties
     * @return void
     */
    public function clearProperties()
    {
        $this->mProperties = [];
    }

    /**
     * Summary of SetProperty
     * @param mixed $inKey
     * @param mixed $inValue
     * @return void
     */
    public function setProperty($inKey, $inValue)
    {
        // Don't store empty keys
        if (empty($inKey)) {
            return;
        }

        if ($this->mFormatProperty) {
            $inValue = $this->formatProperty($inValue);
        }

        $this->mProperties[$inKey] = $inValue;
    }

    /**
     * Format a property
     *
     * @param string|array<mixed>|null $inValue of strings to format
     * @return string|array<mixed> of strings formated
     */
    protected function formatProperty($inValue)
    {
        if (!isset($inValue)) {
            return '';
        }
        if (is_numeric($inValue)) {
            return (string) $inValue;
        }
        if (is_array($inValue)) {
            // Recursive call for arrays
            foreach ($inValue as $key => $value) {
                $inValue[$key] = $this->formatProperty($value);
            }
            return $inValue;
        }
        if (!is_string($inValue) || empty($inValue)) {
            return '';
        }

        // Replace html entities with normal characters
        $str = html_entity_decode($inValue, ENT_COMPAT, 'UTF-8');
        // Replace characters
        if (isset($this->mSearch)) {
            $str = str_replace($this->mSearch, $this->mReplace, $str);
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
        if (!file_put_contents($this->mFileName, $content)) {
            $error = sprintf('Cannot save export to file: %s', $this->mFileName);
            throw new Exception($error);
        }
    }

    /**
     * Send download http headers
     *
     * @param string $inFileName Download file name to display in the browser
     * @param int $inFileSize Download file size
     * @param string $inCodeSet Charset
     * @throws exception if http headers have been already sent
     *
     * @return void
     */
    protected function sendDownloadHeaders($inFileName, $inFileSize = null, $inCodeSet = 'utf-8')
    {
        // Throws excemtion if http headers have been already sent
        $filename = '';
        $linenum = 0;
        if (headers_sent($filename, $linenum)) {
            $error = sprintf('Http headers already sent by file: %s line %d', $filename, $linenum);
            throw new Exception($error);
        }

        $inFileName = str_replace(' ', '', basename($inFileName)); // Cleanup file name
        $ext = strtolower(substr(strrchr($inFileName, '.'), 1));

        switch ($ext) {
            case 'pdf':
                $contentType = 'application/pdf';
                break;
            case 'zip':
                $contentType = 'application/zip';
                break;
            case 'xml':
                $contentType = 'text/xml';
                if (!empty($inCodeSet)) {
                    $contentType .= '; charset=' . $inCodeSet;
                }
                break;
            case 'txt':
                $contentType = 'text/plain';
                if (!empty($inCodeSet)) {
                    $contentType .= '; charset=' . $inCodeSet;
                }
                break;
            case 'csv':
                $contentType = 'text/csv';
                if (!empty($inCodeSet)) {
                    $contentType .= '; charset=' . $inCodeSet;
                }
                break;
            case 'html':
                $contentType = 'text/html';
                if (!empty($inCodeSet)) {
                    $contentType .= '; charset=' . $inCodeSet;
                }
                break;
            default:
                $contentType = 'application/force-download';
                break;
        }

        // Send http headers for download
        header('Content-disposition: attachment; filename="' . $inFileName . '"');
        Header('Content-Type: ' . $contentType);
        //header('Content-Transfer-Encoding: binary');
        if (isset($inFileSize)) {
            header('Content-Length: ' . $inFileSize);
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
        $this->sendDownloadHeaders($this->mFileName, $size);

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
