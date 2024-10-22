<?php
/**
 * BaseMatch class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata;

class BaseMatch
{
    public const ENTITY_URL = 'http://www.wikidata.org/entity/';
    public const ENTITY_PATTERN = '/^\w+$/';
    public const SLEEP_TIME = 50000;

    /** @var BaseCache|null */
    protected $cache;
    /** @var string */
    protected $lang;
    /** @var string|int */
    protected $limit;
    /** @var resource */
    protected $context;

    /**
     * Summary of __construct
     * @param string|null $cacheDir
     * @param string $lang Language (default: en)
     * @param string|int $limit Max count of returning items (default: 10)
     */
    public function __construct($cacheDir = null, $lang = 'en', $limit = 10)
    {
        $this->setCache($cacheDir);
        $this->lang = $lang;
        $this->limit = $limit;
        $this->context = stream_context_create([
            'http' => [
                'timeout' => 60,
            ],
        ]);
    }

    /**
     * Summary of setCache
     * @param string|null $cacheDir
     * @return void
     */
    public function setCache($cacheDir)
    {
        $this->cache = new BaseCache($cacheDir);
    }

    /**
     * Summary of getCache
     * @return mixed
     */
    public function getCache()
    {
        return $this->cache;
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
        if (!empty($link) && str_starts_with($link, (string) static::ENTITY_URL)) {
            return true;
        }
        return false;
    }

    /**
     * Summary of getMatchLink
     * @param string $type
     * @param mixed $value
     * @return string
     */
    public static function getTypeLink($type, $value)
    {
        if (empty($value)) {
            return '';
        }
        $url = match ($type) {
            'goodreads' => GoodReads\GoodReadsMatch::link($value),
            'google' => GoogleBooks\GoogleBooksMatch::link($value),
            'olid' => OpenLibrary\OpenLibraryMatch::link($value),
            'wd' => WikiData\WikiDataMatch::link($value),
            'url' => str_contains($value, '://') ? $value : '',
            'viaf' => 'https://viaf.org/viaf/' . $value,
            'amazon' => 'https://www.amazon.com/dp/' . $value,
            'freebase' => 'https://www.google.com/search?kgmid=' . $value,
            'g_kgmid' => 'https://www.google.com/search?kgmid=' . $value,
            'isbn' => 'https://search.worldcat.org/search?q=bn:' . $value,
            'isfdb' => 'https://www.isfdb.org/cgi-bin/title.cgi?' . $value,
            'ltid' => 'https://www.librarything.com/work/book/' . $value,
            'goodreads_a' => GoodReads\GoodReadsMatch::AUTHOR_URL . $value,
            'goodreads_s' => GoodReads\GoodReadsMatch::SERIES_URL . $value,
            'isfdb_a' => 'https://www.isfdb.org/cgi-bin/ea.cgi?' . $value,
            'isfdb_s' => 'https://www.isfdb.org/cgi-bin/pe.cgi' . $value,
            'ltid_a' => 'https://www.librarything.com/author/' . $value,
            'ltid_s' => 'https://www.librarything.com/nseries/' . $value,
            default => '',
        };
        return $url;
    }
}
