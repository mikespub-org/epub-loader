<?php
/**
 * BaseInfo class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata;

/**
 * BaseInfo class contains informations about a book, author or series
 * and methods to load this informations from multiple sources (eg epub file)
 */
class BaseInfo
{
    public string $basePath = '';

    public string $id = '';

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
     * Loads info from database
     *
     * @param string $basePath base directory
     * @param array<mixed> $data
     *
     * @return self
     */
    public static function load($basePath, $data)
    {
        $baseInfo = new BaseInfo();
        $baseInfo->source = 'database';
        $baseInfo->basePath = $basePath;
        $baseInfo->id = $data['id'] ?? '';
        return $baseInfo;
    }
}
