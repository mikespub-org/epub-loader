<?php
/**
 * SourceReader class
 * |-> BookReader (LOCAL_BOOKS)
 * |-> CalibreReader (CALIBRE_DB)
 * |-> CsvFileReader (CSV_FILES)
 * |-> JsonFileReader (JSON_FILES)
 * |-> CacheReader (CACHE_TYPE)
 * |-> ...
 * implement iterate()
 */

namespace Marsender\EPubLoader\Workflows\Readers;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;

abstract class SourceReader
{
    /** @var array<mixed> */
    public array $messages = [];
    /** @var array<mixed> */
    public array $errors = [];

    /**
     * Load books from <something> in path
     *
     * @param string $basePath base directory
     * @param string $localPath relative to $basePath
     *
     * @return \Generator<int, BookInfo|AuthorInfo|SeriesInfo>
     */
    abstract public function iterate($basePath, $localPath);

    /**
     * Run iterate() generator and get results (without workflow)
     * @param mixed $basePath
     * @param mixed $localPath
     * @return array<mixed>
     */
    public function process($basePath, $localPath)
    {
        $result = [];
        $count = 0;
        $generator = $this->iterate($basePath, $localPath);
        foreach ($generator as $id => $info) {
            $count++;
            $id = $id ?: $count;
            $result[$id] = $info;
        }
        return $result;
    }

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
