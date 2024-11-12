<?php
/**
 * BaseInfo class
 */

namespace Marsender\EPubLoader\Models;

use Marsender\EPubLoader\CalibreDbLoader;

/**
 * BaseInfo class contains informations about a book, author or series
 * and methods to load this informations from multiple sources (eg epub file)
 */
class BaseInfo
{
    public string $source = '';

    public string $basePath = '';

    public string $id = '';

    public bool $loaded = false;

    /**
     * Summary of getTitleSort
     * @param string $str
     * @return string
     */
    public static function getTitleSort($str)
    {
        $str = trim($str, ' -.');
        // @todo add articles to ignore in other languages
        if (!preg_match('/^(The|A|An) /u', $str)) {
            return $str;
        }
        return preg_replace('/^(The|A|An) (.+)$/u', '$2, $1', $str);
    }

    /**
     * Format a string for sort
     *
     * @param string $str Any string
     *
     * @return string Same string without any accents
     */
    public static function getSortString($str)
    {
        $search = [
            '@(*UTF8)[éèêëÉÈÊË]@i',
            '@(*UTF8)[áàâäÁÀÂÄ]@i',
            '@(*UTF8)[íìîïÍÌÎÏ]@i',
            '@(*UTF8)[úùûüÚÙÛÜ]@i',
            '@(*UTF8)[óòôöÓÒÔÖ]@i',
            '@(*UTF8)[œŒ]@i',
            '@(*UTF8)[æÆ]@i',
            '@(*UTF8)[çÇ]@i',
            //'@[ ]@i',
            '@[^a-zA-Z0-9_\-\.,\ ]@',
        ];
        $replace = [
            'e',
            'a',
            'i',
            'u',
            'o',
            'oe',
            'ae',
            'c',
            //'-',
            '',
        ];
        $res = preg_replace($search, $replace, $str);

        // Remove double white spaces
        while (str_contains((string) $res, '  ')) {
            $res = str_replace('  ', ' ', $res);
        }

        $res = trim((string) $res, ' -.,');

        return $res;
    }

    /**
     * Load info from database
     *
     * @param string $basePath base directory
     * @param array<mixed> $data
     * @param ?CalibreDbLoader $loader
     *
     * @return self|null
     */
    public static function load($basePath, $data, $loader = null)
    {
        if (empty($data)) {
            return null;
        }
        $baseInfo = new BaseInfo();
        $baseInfo->source = $data['source'] ?? 'database';
        $baseInfo->basePath = $basePath;
        $baseInfo->id = $data['id'] ?? '';
        if (!empty($loader)) {
            // ...
            $baseInfo->loaded = true;
        }
        return $baseInfo;
    }

    /**
     * Convert from JSON record to BookInfo etc.
     * @param array<mixed> $data
     * @return BookInfo|AuthorInfo|SeriesInfo
     */
    public static function fromJson($data)
    {
        $info = new static();
        foreach ($data as $key => $value) {
            $info->{$key} = static::getValue($key, $value);
        }
        return $info;
    }

    /**
     * Get value for property
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public static function getValue($key, $value)
    {
        switch ($key) {
            case 'authors':
                $infos = [];
                foreach ($value as $id => $item) {
                    $infos[$id] = AuthorInfo::fromJson($item);
                }
                return $infos;
            case 'series':
                $infos = [];
                foreach ($value as $id => $item) {
                    $infos[$id] = SeriesInfo::fromJson($item);
                }
                return $infos;
            case 'books':
                $infos = [];
                foreach ($value as $id => $item) {
                    $infos[$id] = BookInfo::fromJson($item);
                }
                return $infos;
            default:
                return $value;
        }
    }
}
