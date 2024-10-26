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
    use HasAuthorsTrait;
    use HasBooksTrait;
    use HasNoteTrait;

    /** @var array<string> */
    public static array $seriesList = [];

    public static string $notesColName = 'series';

    public string $id = '';

    public string $title = '';

    public string $sort = '';

    public string $link = '';

    public string $index = '';

    /**
     * Get list of seriesId => seriesName
     * @param ?CalibreDbLoader $loader if we need to get the list
     * @return array<string>
     */
    public static function getTitleList($loader = null)
    {
        if (!empty(self::$seriesList) || empty($loader)) {
            return self::$seriesList;
        }
        self::$seriesList = $loader->getSeriesTitles();
        return self::$seriesList;
    }

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
        // From CalibreDbLoader::getSeries():
        // distinct series.id as id, series.name as name, series.sort as sort, series.link as link, author
        // series can have multiple authors
        $seriesInfo = new SeriesInfo();
        $seriesInfo->source = $data['source'] ?? 'database';
        $seriesInfo->basePath = $basePath;
        $seriesInfo->id = $data['id'] ?? '';
        $seriesInfo->title = $data['name'] ?? '';
        $seriesInfo->sort = $data['sort'] ?? static::getTitleSort($seriesInfo->title);
        $seriesInfo->link = $data['link'] ?? '';
        $seriesInfo->index = $data['index'] ?? '';
        if (!empty($data['author'])) {
            $authorId = $data['author'];
            $author = [
                'id' => $authorId,
                'name' => $authorId,
            ];
            $seriesInfo->addAuthor($authorId, $author);
        }
        // for import from metadata (instead of note)
        if (!empty($data['description'])) {
            $seriesInfo->addNote($data['description']);
        }
        if (!empty($data['books'])) {
            // ...
        }
        if (!empty($data['authors'])) {
            // ...
        }
        if (empty($loader) || empty($seriesInfo->id)) {
            $seriesInfo->loaded = false;
            return $seriesInfo;
        }
        // From CalibreDbLoader::getBooksBySeries():
        // id, title, sort, series_index, author, series, identifiers
        $books = $loader->getBooksBySeries($seriesInfo->id);
        foreach ($books as $id => $info) {
            $seriesInfo->addBook($id, $info);
        }
        // From CalibreDbLoader::getSeries():
        // distinct series.id as id, series.name as name, series.sort as sort, series.link as link, author
        // series can have multiple authors
        $series = $loader->getSeries($seriesInfo->id);
        foreach ($series as $id => $info) {
            $authorId = $info['author'];
            $author = [
                'id' => $authorId,
                'name' => $authorId,
            ];
            $seriesInfo->addAuthor($authorId, $author);
        }
        // Get list of authorId => authorName
        $authorList = AuthorInfo::getNameList($loader);
        // Set names for authors
        $seriesInfo->setAuthorNames($authorList);

        // Get list of seriesId => seriesName
        $seriesList = SeriesInfo::getTitleList($loader);
        // Get list of bookId => bookTitle
        $bookList = BookInfo::getTitleList($loader);
        // Set titles for books & series in authors
        $seriesInfo->fixAuthors($bookList, $seriesList);
        // Set names for authors & titles for series in books
        $seriesInfo->fixBooks($authorList, $seriesList);

        $seriesInfo->getNote($loader);

        $seriesInfo->loaded = true;
        return $seriesInfo;
    }
}
