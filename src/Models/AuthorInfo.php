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
    use HasBooksTrait;
    use HasNoteTrait;
    use HasSeriesTrait;
    use HasIdentifiersTrait;

    /** @var array<string> */
    public static array $authorList = [];

    public static string $notesColName = 'authors';

    public string $id = '';

    public string $name = '';

    public string $sort = '';

    public string $link = '';

    public string $image = '';

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
     * Get list of authorId => authorName
     * @param ?CalibreDbLoader $loader if we need to get the list
     * @return array<string>
     */
    public static function getNameList($loader = null)
    {
        if (!empty(self::$authorList) || empty($loader)) {
            return self::$authorList;
        }
        self::$authorList = $loader->getAuthorNames();
        return self::$authorList;
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
        // for import from metadata (instead of note)
        if (!empty($data['description'])) {
            $authorInfo->addNote($data['description']);
        }
        $authorInfo->image = $data['image'] ?? '';
        if (!empty($data['books'])) {
            // ...
        }
        if (!empty($data['series'])) {
            // ...
        }
        if (!empty($data['identifiers'])) {
            // ...
        }
        if (empty($loader) || empty($authorInfo->id)) {
            $authorInfo->loaded = false;
            return $authorInfo;
        }
        // From CalibreDbLoader::getBooksByAuthor():
        // id, title, sort, series_index, author, series, identifiers
        $books = $loader->getBooksByAuthor($authorInfo->id);
        foreach ($books as $id => $info) {
            $authorInfo->addBook($id, $info);
        }
        // From CalibreDbLoader::getSeriesByAuthor():
        // distinct series.id as id, series.name as name, series.sort as sort, series.link as link, author
        // series can have multiple authors
        $series = $loader->getSeriesByAuthor($authorInfo->id);
        foreach ($series as $id => $info) {
            $seriesId = $info['id'];
            if (!empty($authorInfo->series[$seriesId])) {
                $authorId = $info['author'];
                $author = [
                    'id' => $authorId,
                    'name' => $authorId,
                ];
                $authorInfo->series[$seriesId]->addAuthor($authorId, $author);
                continue;
            }
            $authorInfo->addSeries($seriesId, $info);
        }
        // Get list of authorId => authorName
        $authorList = AuthorInfo::getNameList($loader);
        // Get list of seriesId => seriesName
        $seriesList = SeriesInfo::getTitleList($loader);
        // Get list of bookId => bookTitle
        $bookList = BookInfo::getTitleList($loader);
        // Set names for authors & titles for series in books
        $authorInfo->fixBooks($authorList, $seriesList);
        // Set names for authors & titles for books in series
        $authorInfo->fixSeries($authorList, $bookList);
        // @todo series have no books here

        $authorInfo->getNote($loader);

        $authorInfo->loaded = true;
        return $authorInfo;
    }
}
