<?php
/**
 * Workflow class
 */

namespace Marsender\EPubLoader\Workflows;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Exception;

class Workflow
{
    public const CALIBRE_DB = 1;
    public const LOCAL_BOOKS = 2;
    public const CSV_FILES = 3;
    public const JSON_FILES = 4;
    public const CACHE_TYPE = 5;
    public const CALLBACK = 6;

    /** @var Readers\SourceReader|null */
    public $reader = null;
    /** @var Writers\TargetWriter|null */
    public $writer = null;
    /** @var array<Converters\Converter> */
    public $converters = [];
    protected int $nbOk = 0;
    protected int $nbError = 0;

    /**
     * Create an import/export workflow
     *
     * @param Readers\SourceReader|null $reader
     * @param Writers\TargetWriter|null $writer
     * @param array<Converters\Converter>|null $converters
     * @param string $bookIdsFileName File name containing a map of file names to calibre book ids
     * @throws Exception if error
     */
    public function __construct($reader = null, $writer = null, $converters = null, $bookIdsFileName = '')
    {
        $this->reader = $reader;
        $this->writer = $writer;
        $converters ??= [];
        if (!empty($bookIdsFileName)) {
            $converters[] = new Converters\IdMapper($bookIdsFileName);
        }
        $this->converters = $converters;
    }

    /**
     * Load info from <reader> in path and add to <writer>
     *
     * @param string $basePath base directory
     * @param string $localPath relative to $basePath
     *
     * @return void
     */
    public function process($basePath, $localPath)
    {
        $generator = $this->reader->iterate($basePath, $localPath);
        foreach ($generator as $id => $info) {
            try {
                [$info, $id] = $this->convert($info, $id);
            } catch (Exception $e) {
                $this->writer->addError("Convert item " . (string) $id, $e->getMessage());
                $this->nbError++;
                // @todo do not stop processing here?
            }
            try {
                $this->addInfo($info, $id);
                $this->nbOk++;
            } catch (Exception $e) {
                $this->writer->addError("Add item " . (string) $id, $e->getMessage());
                $this->nbError++;
            }
        }
        $dirName = $basePath . DIRECTORY_SEPARATOR . $localPath;
        $message = sprintf('Total write to %s - %d files OK - %d files Error', $dirName, $this->nbOk, $this->nbError);
        $this->writer->addMessage($dirName, $message);
    }

    /**
     * Convert info and/or id
     *
     * @param BookInfo|AuthorInfo|SeriesInfo $info object
     * @param int $id id in the calibre db (or 0 for auto incrementation)
     * @return array{0: BookInfo|AuthorInfo|SeriesInfo, 1: int}
     */
    public function convert($info, $id)
    {
        foreach ($this->converters as $converter) {
            [$info, $id] = $converter->convert($info, $id);
        }
        return [$info, $id];
    }

    /**
     * Add new info to the target
     *
     * @param BookInfo|AuthorInfo|SeriesInfo $info object
     * @param int $id id in the calibre db (or 0 for auto incrementation)
     * @return void
     */
    public function addInfo($info, $id)
    {
        switch ($info::class) {
            case BookInfo::class:
                $this->writer->addBook($info, $id);
                break;
            case AuthorInfo::class:
                $this->writer->addAuthor($info, $id);
                break;
            case SeriesInfo::class:
                $this->writer->addSeries($info, $id);
                break;
            default:
                $error = sprintf('Incorrect info type: %s', $info::class);
                throw new Exception($error);
        }
    }

    /**
     * Summary of getMessages
     * @return array<mixed>
     */
    public function getMessages()
    {
        return $this->reader->messages + $this->writer->messages;
    }

    /**
     * Summary of getErrors
     * @return array<mixed>
     */
    public function getErrors()
    {
        return $this->reader->errors + $this->writer->errors;
    }

    /**
     * Get reader instance
     *
     * @param int    $type Reader type
     * @param string $path Reader path
     * @throws Exception if error
     * @return Readers\SourceReader
     */
    public static function getReader($type, $path = '')
    {
        switch ($type) {
            case self::CALIBRE_DB:
                return new Readers\CalibreReader($path);
            case self::LOCAL_BOOKS:
                return new Readers\BookReader();
            case self::CSV_FILES:
                return new Readers\CsvFileReader();
            case self::JSON_FILES:
                return new Readers\JsonFileReader($path);
            case self::CACHE_TYPE:
                return new Readers\CacheReader($path);
            case self::CALLBACK:
            default:
                $error = sprintf('Incorrect reader type: %d', $type);
                throw new Exception($error);
        }
    }

    /**
     * Get writer instance
     *
     * @param int   $type Writer type
     * @param mixed $path Writer path or callbacks
     * @param bool  $create Force creation
     * @throws Exception if error
     * @return Writers\TargetWriter
     */
    public static function getWriter($type, $path = '', $create = false)
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
            case self::CACHE_TYPE:
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
        // @todo handle bookIdsFileName & converter?
        $reader = self::getReader($sourceType, $sourcePath);
        $writer = self::getWriter($targetType, $targetPath, $create);
        return new self($reader, $writer);
    }
}
