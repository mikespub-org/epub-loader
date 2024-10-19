<?php
/**
 * BaseCache class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata;

use Exception;

/**
 * Metadata Cache for use in Match, Import etc.
 *
 * Usage:
 * ```php
 * $cache = new GoodReadsCache($cacheDir);
 * // ...
 * $cacheFile = $cache->getAuthor($authorId);
 * if ($cache->hasCache($cacheFile)) {
 *     return $cache->loadCache($cacheFile);
 * }
 * // ...
 * $data = ['result => '...'];
 * $cache->saveCache($cacheFile, $data);
 * ```
 */
class BaseCache
{
    public const CACHE_TYPES = [];

    public static int $limit = 500;
    public static int $expires = 2 * 60 * 60;
    /** @var string|null */
    protected $cacheDir;

    /**
     * Summary of __construct
     * @param string|null $cacheDir
     */
    public function __construct($cacheDir = null)
    {
        $this->cacheDir = $cacheDir;
        if (!empty($this->cacheDir)) {
            $this->prepareCacheDir();
        }
    }

    /**
     * Summary of prepareCacheDir
     * @throws \Exception
     * @return void
     */
    protected function prepareCacheDir()
    {
        foreach (static::CACHE_TYPES as $cacheType) {
            $makeDir = $this->cacheDir . '/' . $cacheType;
            if (!is_dir($makeDir) && !mkdir($makeDir, 0o755, true)) {
                throw new Exception('Cannot create directory: ' . $makeDir);
            }
        }
    }

    /**
     * Summary of hasCache
     * @param string $cacheFile
     * @return string|false
     */
    public function hasCache($cacheFile)
    {
        if (empty($this->cacheDir) || empty($cacheFile)) {
            return false;
        }
        if (is_file($cacheFile)) {
            return $cacheFile;
        }
        return false;
    }

    /**
     * Summary of loadCache
     * @param string $cacheFile
     * @return mixed
     */
    public function loadCache($cacheFile)
    {
        if (empty($this->cacheDir) || empty($cacheFile)) {
            return null;
        }
        return json_decode(file_get_contents($cacheFile), true);
    }

    /**
     * Summary of saveCache
     * @param string $cacheFile
     * @param mixed $data
     * @return void
     */
    public function saveCache($cacheFile, $data)
    {
        if (empty($this->cacheDir) || empty($cacheFile)) {
            return;
        }
        file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Summary of saveFile
     * @param string $cacheFile
     * @param string $content
     * @return void
     */
    public function saveFile($cacheFile, $content)
    {
        if (empty($this->cacheDir) || empty($cacheFile)) {
            return;
        }
        file_put_contents($cacheFile, $content);
    }

    /**
     * Summary of cachedMethod
     * @param string $filePath relative to cacheDir
     * @param callable $callable like [$this, 'getSomething']
     * @param array<mixed> $args
     * @return mixed
     */
    public function cachedMethod($filePath, $callable, ...$args)
    {
        $cacheFile = $this->cacheDir . '/' . $filePath;
        if ($this->hasCache($cacheFile)) {
            if (filemtime($cacheFile) > time() - static::$expires) {
                return $this->loadCache($cacheFile);
            }
        }
        $data = $callable(...$args);
        $this->saveCache($cacheFile, $data);
        return $data;
    }

    /**
     * Recursive get files
     *
     * @param string $inPath Base directory to search in
     * @param string $inPattern Search pattern
     * @param bool $inStrip Strip path and pattern from result (e.g. for entity ids)
     * @return array<string>
     */
    public static function getFiles($inPath = '', $inPattern = '*.epub', $inStrip = false)
    {
        $res = [];

        // Check path
        if (!is_dir($inPath)) {
            return $res;
        }

        // Get the list of directories
        if (substr($inPath, -1) != DIRECTORY_SEPARATOR) {
            $inPath .= DIRECTORY_SEPARATOR;
        }
        // Simple cases only, e.g. *.epub or *.en.json
        $suffix = str_replace('*', '', $inPattern);

        // Add files from the current directory
        $files = glob($inPath . $inPattern, GLOB_MARK | GLOB_NOSORT);
        foreach ($files as $item) {
            if (substr($item, -1) == DIRECTORY_SEPARATOR) {
                continue;
            }
            if ($inStrip) {
                $res[] = basename($item, $suffix);
            } else {
                $res[] = $item;
            }
        }

        // Scan sub directories
        $paths = glob($inPath . '*', GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);
        foreach ($paths as $path) {
            $res = array_merge($res, static::getFiles($path, $inPattern));
        }

        // Sort in "natural" order for increasing id's
        sort($res, SORT_NATURAL);

        return $res;
    }

    /**
     * Summary of getCacheStats
     * @param string $cacheDir
     * @return array<mixed>
     */
    public static function getCacheStats($cacheDir)
    {
        $caches = [];
        if (empty($cacheDir) || !is_dir($cacheDir)) {
            return $caches;
        }
        // cache labels for display
        $labels = ['GoodReads', 'GoogleBooks', 'OpenLibrary', 'WikiData'];
        foreach ($labels as $label) {
            $name = strtolower($label);
            $cache = static::getCacheInstance($cacheDir, $name);
            $caches[$label] = $cache->getStats();
        }
        return $caches;
    }

    /**
     * Summary of getCacheEntries
     * @param string $cacheDir
     * @param string $cacheName
     * @param string $cacheType
     * @param int|null $offset
     * @return array<mixed>
     */
    public static function getCacheEntries($cacheDir, $cacheName, $cacheType, $offset = null)
    {
        $entries = [];
        if (empty($cacheDir) || !is_dir($cacheDir)) {
            return $entries;
        }
        $cache = static::getCacheInstance($cacheDir, $cacheName);
        return $cache->getEntries($cacheType, $offset);
    }

    /**
     * Summary of getCacheEntry
     * @param string $cacheDir
     * @param string $cacheName
     * @param string $cacheType
     * @param string $cacheEntry
     * @return array<mixed>|null
     */
    public static function getCacheEntry($cacheDir, $cacheName, $cacheType, $cacheEntry)
    {
        $entry = null;
        if (empty($cacheDir) || !is_dir($cacheDir)) {
            return $entry;
        }
        // check for invalid path element in entry
        if (empty($cacheEntry) || $cacheEntry != basename($cacheEntry)) {
            throw new Exception(message: 'Invalid cache entry');
        }
        $cache = static::getCacheInstance($cacheDir, $cacheName);
        return $cache->getEntry($cacheType, $cacheEntry);
    }

    /**
     * Summary of getCacheInstance
     * @param string $cacheDir
     * @param string $cacheName
     * @return GoodReads\GoodReadsCache|GoogleBooks\GoogleBooksCache|OpenLibrary\OpenLibraryCache|WikiData\WikiDataCache
     */
    public static function getCacheInstance($cacheDir, $cacheName)
    {
        $cacheName = strtolower($cacheName);
        return match ($cacheName) {
            'goodreads' => new GoodReads\GoodReadsCache($cacheDir),
            'googlebooks' => new GoogleBooks\GoogleBooksCache($cacheDir),
            'openlibrary' => new OpenLibrary\OpenLibraryCache($cacheDir),
            'wikidata' => new WikiData\WikiDataCache($cacheDir),
            default => throw new Exception('Invalid cache name'),
        };
    }
}
