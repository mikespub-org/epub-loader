<?php
/**
 * Workflow class
 */

namespace Marsender\EPubLoader\Workflows;

use Marsender\EPubLoader\Models\BookInfo;
use Exception;

class Workflow
{
    public const CALIBRE_DB = 1;
    public const LOCAL_BOOKS = 2;
    public const CSV_FILES = 3;
    public const JSON_FILES = 4;
    public const CALLBACK = 5;

    /** @var Readers\SourceReader|null */
    public $reader = null;
    /** @var Writers\TargetWriter|null */
    public $writer = null;
    /** @var Converters\Converter|null */
    public $converter = null;

    /**
     * Create an import/export workflow
     *
     * @param mixed $reader
     * @param mixed $writer
     * @param mixed $converter
     * @param string $bookIdsFileName File name containing a map of file names to calibre book ids
     * @throws Exception if error
     */
    public function __construct($reader = null, $writer = null, $converter = null, $bookIdsFileName = '')
    {
        $this->reader = $reader;
        $this->writer = $writer;
        if (!empty($bookIdsFileName) && empty($converter)) {
            $converter = new Converters\IdMapper($bookIdsFileName);
        }
        $this->converter = $converter;
    }

    /**
     * Summary of getBookId
     * @param string $bookFileName
     * @return int
     */
    public function getBookId($bookFileName)
    {
        $bookId = 0;
        if ($this->converter instanceof Converters\IdMapper) {
            $bookId = $this->converter->getBookId($bookFileName);
        }
        return $bookId;
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
        $this->reader->process($basePath, $localPath);
    }

    /**
     * Add a new book to the target
     *
     * @param BookInfo $bookInfo BookInfo object
     * @param int $bookId Book id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     *
     * @return void
     */
    public function addBook($bookInfo, $bookId = 0)
    {
        $this->writer->addBook($bookInfo, $bookId);
    }

    /**
     * Summary of getMessages
     * @return array<mixed>
     */
    public function getMessages()
    {
        return $this->reader->messages;
    }

    /**
     * Summary of getErrors
     * @return array<mixed>
     */
    public function getErrors()
    {
        return $this->reader->errors;
    }

    /**
     * Get reader instance
     *
     * @param ?Workflow $workflow
     * @param int       $type Reader type
     * @param string    $path Reader path
     * @throws Exception if error
     * @return Readers\SourceReader
     */
    public static function getReader($workflow, $type, $path = '')
    {
        switch ($type) {
            case self::CALIBRE_DB:
                return new Readers\CalibreReader($workflow, $path);
            case self::LOCAL_BOOKS:
                return new Readers\BookReader($workflow);
            case self::CSV_FILES:
                return new Readers\CsvFileReader($workflow);
            case self::JSON_FILES:
                return new Readers\JsonFileReader($workflow, $path);
            case self::CALLBACK:
            default:
                $error = sprintf('Incorrect reader type: %d', $type);
                throw new Exception($error);
        }
    }

    /**
     * Get writer instance
     *
     * @param ?Workflow $workflow
     * @param int       $type Writer type
     * @param string|mixed    $path Writer path or callbacks
     * @param bool      $create Force creation
     * @throws Exception if error
     * @return Writers\TargetWriter
     */
    public static function getWriter($workflow, $type, $path = '', $create = false)
    {
        switch ($type) {
            case self::CSV_FILES:
                return new Writers\CsvFileWriter($path, $create);
            case self::JSON_FILES:
                return new Writers\JsonFileWriter($path, $create);
            case self::CALIBRE_DB:
                return new Writers\CalibreWriter($path, $create);
            case self::CALLBACK:
                return new Writers\CallbackWriter($path);
            case self::LOCAL_BOOKS:
            default:
                $error = sprintf('Incorrect writer type: %d', $type);
                throw new Exception($error);
        }
    }

    /**
     * Summary of getWorkflow
     * @param int $sourceType
     * @param string $sourcePath
     * @param int $targetType
     * @param string|mixed $targetPath
     * @param mixed $create
     * @return self
     */
    public static function getWorkflow($sourceType, $sourcePath, $targetType, $targetPath, $create = false)
    {
        $workflow = new self();
        // @todo handle bookIdsFileName & converter?
        $workflow->reader = self::getReader($workflow, $sourceType, $sourcePath);
        $workflow->writer = self::getWriter($workflow, $targetType, $targetPath, $create);
        return $workflow;
    }
}
