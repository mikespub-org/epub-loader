<?php
/**
 * BaseMatch class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

use Exception;

class BaseMatch
{
    public const ENTITY_URL = 'http://www.wikidata.org/entity/';
    public const ENTITY_PATTERN = '/^\w+$/';
    public const CACHE_TYPES = [];

    /** @var string|null */
    protected $cacheDir;
    /** @var string */
    protected $lang;
    /** @var string|int */
    protected $limit;

    /**
     * Summary of __construct
     * @param string|null $cacheDir
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 10)
     */
    public function __construct($cacheDir = null, $lang = 'en', $limit = 10)
    {
        $this->cacheDir = $cacheDir;
        if (!empty($this->cacheDir)) {
            $this->prepareCacheDir();
        }
        $this->lang = $lang;
        $this->limit = $limit;
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
     * Summary of loadCache
     * @param string $cacheFile
     * @return mixed
     */
    public function loadCache($cacheFile)
    {
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
        file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Summary of link
     * @param string $entityId
     * @return string
     */
    public static function link($entityId)
    {
        return static::ENTITY_URL . $entityId;
    }

    /**
     * Summary of entity
     * @param string $link
     * @return string
     */
    public static function entity($link)
    {
        return str_replace(static::ENTITY_URL, '', $link);
    }

    /**
     * Summary of isValidEntity
     * @param string $entityId
     * @return bool
     */
    public static function isValidEntity($entityId)
    {
        if (!empty($entityId) && preg_match(static::ENTITY_PATTERN, $entityId)) {
            return true;
        }
        return false;
    }

    /**
     * Summary of isValidLink
     * @param string $link
     * @return bool
     */
    public static function isValidLink($link)
    {
        if (!empty($link) && str_starts_with($link, static::ENTITY_URL)) {
            return true;
        }
        return false;
    }
}
