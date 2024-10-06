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

        sort($res);

        return $res;
    }
}
