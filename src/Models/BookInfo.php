<?php
/**
 * BookInfo class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Models;

use Marsender\EPubLoader\CalibreDbLoader;

/**
 * BookInfo class contains informations about a book,
 * and methods to load this informations from multiple sources (eg epub file)
 */
class BookInfo extends BaseInfo
{
    use HasAuthorsTrait;
    use HasIdentifiersTrait;
    use HasSeriesTrait;

    /** @var array<string> */
    public static array $bookList = [];

    public string $id = '';

    public string $format = '';

    /** @var array<mixed>|null */
    public $formats = null;

    public string $path = '';

    public string $uuid = '';

    public string $uri = '';

    public string $title = '';

    public string $sort = '';

    public string $language = '';

    public string $description = '';

    /** @var array<string>|null */
    public $subjects = null;

    public string $cover = '';

    public string $isbn = '';

    public string $lccn = '';

    public string $rights = '';

    public string $publisher = '';

    public string $creationDate = '';

    public string $modificationDate = '';

    public string $timestamp = '0';

    public float|int|null $rating = null;

    /**
     * Format an date from a date
     *
     * @param string $date
     *
     * @return ?string Sql formated date
     */
    public static function getSqlDate($date)
    {
        if (empty($date)) {
            return null;
        }

        $date = new \DateTime($date);
        $res = $date->format('Y-m-d H:i:s');

        return $res;
    }

    /**
     * Format a timestamp from a date
     *
     * @param string $date
     *
     * @return ?int Timestamp
     */
    public static function getTimeStamp($date)
    {
        if (empty($date)) {
            return null;
        }

        $date = new \DateTime($date);
        $res = $date->getTimestamp();

        return $res;
    }

    /**
     * Create a new unique id (same as shell uuidgen)
     *
     * @return void
     */
    public function createUuid()
    {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        $this->uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Get list of bookId => bookTitle
     * @param ?CalibreDbLoader $loader if we need to get the list
     * @return array<string>
     */
    public static function getTitleList($loader = null)
    {
        /**
        if (!empty(self::$bookList) || empty($loader)) {
            return self::$bookList;
        }
        self::$bookList = $loader->getBookTitles();
         */
        return self::$bookList;
    }

    /**
     * Load book info from database
     *
     * @param string $basePath base directory
     * @param array<mixed> $data
     * @param ?CalibreDbLoader $loader if we need to load book details
     *
     * @return self|null
     */
    public static function load($basePath, $data, $loader = null)
    {
        if (empty($data)) {
            return null;
        }
        // From CalibreDbLoader::getBooks():
        // id, title, sort, series_index, author, series, identifiers
        $bookInfo = new BookInfo();
        $bookInfo->source = $data['source'] ?? 'database';
        $bookInfo->basePath = $basePath;
        $bookInfo->id = $data['id'] ?? '';
        if (!empty($loader) && !empty($bookInfo->id)) {
            $details = new BookDetails($loader->getDbConnection());
            $data = array_replace($data, $details->getBookDetails($bookInfo->id));
            $bookInfo->loaded = true;
        } else {
            // From CalibreDbLoader::getBooks():
            if (!empty($data['author']) && is_numeric($data['author'])) {
                $authorId = $data['author'];
                $data['authors'] = [];
                $data['authors'][$authorId] = [
                    'id' => $authorId,
                    'name' => $authorId,
                    'source' => $bookInfo->source,
                ];
            }
            // From CalibreDbLoader::getBooks():
            if (!empty($data['series']) && is_numeric($data['series'])) {
                $seriesId = $data['series'];
                $data['series'] = [];
                $data['series'][$seriesId] = [
                    'id' => $seriesId,
                    'name' => $seriesId,
                    'index' => $data['series_index'],
                    'source' => $bookInfo->source,
                ];
            }
            $bookInfo->loaded = false;
        }
        $bookInfo->title = $data['title'] ?? '';
        $bookInfo->sort = $data['sort'] ?? static::getTitleSort($bookInfo->title);
        $data['authors'] ??= [];
        foreach ($data['authors'] as $id => $info) {
            $bookInfo->addAuthor($id, $info);
        }
        $data['series'] ??= [];
        foreach ($data['series'] as $id => $info) {
            $bookInfo->addSeries($id, $info);
        }
        $data['identifiers'] ??= [];
        foreach ($data['identifiers'] as $id => $info) {
            $bookInfo->addIdentifier($id, $info);
        }
        // @todo add other fields
        $bookInfo->format = $data['format'] ?? '';
        $bookInfo->formats = $data['formats'] ?? [];
        if (empty($bookInfo->format) && !empty($bookInfo->formats)) {
            $bookInfo->format = (string) array_key_first($bookInfo->formats);
        }
        $bookInfo->format = strtolower($bookInfo->format);
        $bookInfo->path = $data['path'] ?? '';
        $bookInfo->uuid = $data['uuid'] ?? '';
        $bookInfo->uri = $data['uri'] ?? '';
        $bookInfo->language = $data['language'] ?? '';
        $bookInfo->description = $data['description'] ?? '';
        $bookInfo->subjects = $data['subjects'] ?? [];
        $bookInfo->cover = $data['cover'] ?? '';
        $bookInfo->isbn = $data['isbn'] ?? '';
        $bookInfo->rights = $data['rights'] ?? '';
        $bookInfo->publisher = $data['publisher'] ?? '';
        $bookInfo->creationDate = $data['pubdate'] ?? '';
        $bookInfo->modificationDate = $data['last_modified'] ?? '';
        $bookInfo->timestamp = $data['timestamp'] ?? '';
        if (isset($data['rating'])) {
            $bookInfo->rating = (float) $data['rating'];
        }
        // Set isbn, lccn, uri etc. based on identifiers and vice-versa
        $bookInfo->fixIdentifiers();
        if (empty($bookInfo->cover) && !empty($bookInfo->path)) {
            if (!str_contains($bookInfo->path, '://')) {
                $path = $basePath . '/' . $bookInfo->path;
                if (is_dir($path) && is_file($path . '/cover.jpg')) {
                    $bookInfo->cover = $path . '/cover.jpg';
                }
            }
        }
        return $bookInfo;
    }
}
