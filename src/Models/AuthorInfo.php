<?php
/**
 * AuthorInfo class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Models;

use Marsender\EPubLoader\CalibreDbLoader;

/**
 * AuthorInfo class contains informations about an author,
 * and methods to load this informations from multiple sources (eg epub file)
 */
class AuthorInfo extends BaseInfo
{
    public static string $notesColName = 'authors';

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

    /**
     * Load author info from database
     *
     * @param string $basePath base directory
     * @param array<mixed> $data
     * @param ?CalibreDbLoader $loader if we need to load books & series
     *
     * @return self|null
     */
    public static function load($basePath, $data, $loader = null)
    {
        if (empty($data)) {
            return null;
        }
        $authorInfo = new AuthorInfo();
        $authorInfo->source = $data['source'] ?? 'database';
        $authorInfo->basePath = $basePath;
        $authorInfo->id = $data['id'] ?? '';
        $authorInfo->name = $data['name'] ?? '';
        $authorInfo->sort = $data['sort'] ?? static::getNameSort($authorInfo->name);
        $authorInfo->link = $data['link'] ?? '';
        if (!empty($data['books'])) {
            // ...
        }
        if (!empty($data['series'])) {
            // ...
        }
        if (empty($loader) || empty($authorInfo->id)) {
            return $authorInfo;
        }
        // From CalibreDbLoader::getBooksByAuthor():
        // id, title, sort, series_index, author, series, identifiers
        $books = $loader->getBooksByAuthor($authorInfo->id);
        foreach ($books as $id => $info) {
            $authorInfo->books[$id] = BookInfo::load($basePath, $info);
        }
        // From CalibreDbLoader::getSeriesByAuthor():
        // distinct series.id as id, series.name as name, series.sort as sort, series.link as link, author
        // series can have multiple authors
        $series = $loader->getSeriesByAuthor($authorInfo->id);
        foreach ($series as $id => $info) {
            $seriesId = $info['id'];
            $authorInfo->series[$seriesId] = SeriesInfo::load($basePath, $info);
        }
        return $authorInfo;
    }
}
