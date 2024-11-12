<?php
/**
 * CacheReader class
 */

namespace Marsender\EPubLoader\Workflows\Readers;

use Marsender\EPubLoader\Metadata\BaseCache;
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;

class CacheReader extends JsonFileReader
{
    /**
     * Load books from cache
     *
     * @param string $cacheName cacheName
     * @param string $cacheType cacheType
     *
     * @return \Generator<int, BookInfo|AuthorInfo|SeriesInfo>
     */
    public function iterate($cacheName, $cacheType)
    {
        switch ($cacheName) {
            case 'googlebooks':
                $basePath = $this->cacheDir . '/google';
                $jsonPath = $cacheType;
                $pattern = $this->pattern;
                break;
            case 'openlibrary':
                $basePath = $this->cacheDir . '/' . $cacheName;
                // @see OpenLibraryCache::getWorkIds()
                switch ($cacheType) {
                    case 'entities/A':
                        $jsonPath = 'entities';
                        $pattern = '*A.en.json';
                        break;
                    case 'entities/W':
                        $jsonPath = 'entities';
                        $pattern = '*W.en.json';
                        break;
                    default:
                        $jsonPath = $cacheType;
                        $pattern = $this->pattern;
                        break;
                }
                break;
            default:
                $basePath = $this->cacheDir . '/' . $cacheName;
                $jsonPath = $cacheType;
                $pattern = $this->pattern;
                break;
        }
        $fileList = BaseCache::getFiles($basePath . DIRECTORY_SEPARATOR . $jsonPath, $pattern);
        foreach ($fileList as $file) {
            yield from $this->loadFromJsonFile($basePath, $file);
        }
        $dirName = $cacheName . ' ' . $cacheType;
        $message = sprintf('Total read from %s - %d files OK - %d files Error', $dirName, $this->nbOk, $this->nbError);
        $this->addMessage($dirName, $message);
    }
}
