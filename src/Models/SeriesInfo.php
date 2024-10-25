<?php
/**
 * SeriesInfo class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Models;

use Marsender\EPubLoader\CalibreDbLoader;

/**
 * SeriesInfo class contains informations about a series,
 * and methods to load this informations from multiple sources (eg epub file)
 */
class SeriesInfo extends BaseInfo
{
    public static string $notesColName = 'series';

    public string $id = '';

    public string $title = '';

    public string $sort = '';

    public string $link = '';

    public string $index = '';

    /** @var array<BookInfo> */
    public array $books = [];

    /** @var array<AuthorInfo> */
    public array $authors = [];

    /**
     * Load series info from database
     *
     * @param string $basePath base directory
     * @param array<mixed> $data
     * @param ?CalibreDbLoader $loader if we need to load books & authors
     *
     * @return self|null
     */
    public static function load($basePath, $data, $loader = null)
    {
        if (empty($data)) {
            return null;
        }
        $seriesInfo = new SeriesInfo();
        $seriesInfo->source = $data['source'] ?? 'database';
        $seriesInfo->basePath = $basePath;
        $seriesInfo->id = $data['id'] ?? '';
        $seriesInfo->title = $data['name'] ?? '';
        $seriesInfo->sort = $data['sort'] ?? static::getTitleSort($seriesInfo->title);
        $seriesInfo->link = $data['link'] ?? '';
        $seriesInfo->index = $data['index'] ?? '';
        if (!empty($data['author'])) {
            // ...
        }
        if (!empty($data['books'])) {
            // ...
        }
        if (!empty($data['authors'])) {
            // ...
        }
        if (empty($loader) || empty($seriesInfo->id)) {
            return $seriesInfo;
        }
        // From CalibreDbLoader::getBooksBySeries():
        // id, title, sort, series_index, author, series, identifiers
        $books = $loader->getBooksBySeries($seriesInfo->id);
        // From CalibreDbLoader::getSeries():
        // distinct series.id as id, series.name as name, series.sort as sort, series.link as link, author
        // series can have multiple authors
        $series = $loader->getSeries($seriesInfo->id);
        return $seriesInfo;
    }
}
