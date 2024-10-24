<?php
/**
 * BookInfo class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata;

/**
 * BookInfo class contains informations about a book,
 * and methods to load this informations from multiple sources (eg epub file)
 */
class BookInfo extends BaseInfo
{
    public string $basePath = '';

    public string $id = '';

    public string $format = '';

    public string $path = '';

    public string $name = '';

    public string $uuid = '';

    public string $uri = '';

    public string $title = '';

    /** @var array<mixed>|null */
    public $authors = null;

    /** @var array<string>|null */
    public $authorIds = null;

    /** @var array<AuthorInfo> */
    public array $authorsTodo = [];

    public string $language = '';

    public string $description = '';

    /** @var array<string>|null */
    public $subjects = null;

    public string $cover = '';

    public string $isbn = '';

    public string $rights = '';

    public string $publisher = '';

    public string $serie = '';

    /** @var array<string>|null */
    public $serieIds = null;

    public string $serieIndex = '';

    /** @var array<SeriesInfo> */
    public array $series = [];

    public string $creationDate = '';

    public string $modificationDate = '';

    public string $timeStamp = '0';

    public float|int|null $rating = null;

    /** @var array<mixed>|null */
    public $identifiers = null;

    public string $source = '';

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
}
