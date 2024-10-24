<?php
/**
 * AuthorInfo class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata;

/**
 * AuthorInfo class contains informations about an author,
 * and methods to load this informations from multiple sources (eg epub file)
 */
class AuthorInfo extends BaseInfo
{
    public string $basePath = '';

    public string $id = '';

    public string $name = '';

    public string $sort = '';

    public string $link = '';

    /** @var array<SeriesInfo> */
    public array $series = [];

    /** @var array<BookInfo> */
    public array $books = [];

    /**
     * Sort author by Lastname, Firstname(s)
     *
     * @param string $str
     * @return string
     */
    public static function getNameSort($str)
    {
        // drop (ed.) etc. from author name
        if (str_contains($str, ' (')) {
            $str = explode(' (', $str)[0];
        }
        // no space left to split on
        if (!str_contains($str, ' ')) {
            return $str;
        }
        // convert to Lastname, Firstname(s)
        $pieces = explode(' ', $str);
        $last = array_pop($pieces);
        return $last . ', ' . implode(' ', $pieces);
    }
}
