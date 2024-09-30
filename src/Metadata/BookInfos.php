<?php
/**
 * BookInfos class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata;

/**
 * BookInfos class contains informations about a book,
 * and methods to load this informations from multiple sources (eg epub file)
 */
class BookInfos
{
    public string $mBasePath = '';

    public string $mFormat = '';

    public string $mPath = '';

    public string $mName = '';

    public string $mUuid = '';

    public string $mUri = '';

    public string $mTitle = '';

    /** @var array<mixed>|null */
    public $mAuthors = null;

    public string $mLanguage = '';

    public string $mDescription = '';

    /** @var array<string>|null */
    public $mSubjects = null;

    public string $mCover = '';

    public string $mIsbn = '';

    public string $mRights = '';

    public string $mPublisher = '';

    public string $mSerie = '';

    public string $mSerieIndex = '';

    public string $mCreationDate = '';

    public string $mModificationDate = '';

    public string $mTimeStamp = '0';

    /**
     * Format an date from a date
     *
     * @param string $inDate
     *
     * @return ?string Sql formated date
     */
    public static function getSqlDate($inDate)
    {
        if (empty($inDate)) {
            return null;
        }

        $date = new \DateTime($inDate);
        $res = $date->format('Y-m-d H:i:s');

        return $res;
    }

    /**
     * Format a timestamp from a date
     *
     * @param string $inDate
     *
     * @return ?int Timestamp
     */
    public static function getTimeStamp($inDate)
    {
        if (empty($inDate)) {
            return null;
        }

        $date = new \DateTime($inDate);
        $res = $date->getTimestamp();

        return $res;
    }

    /**
     * Format a string for sort
     *
     * @param string $inStr Any string
     *
     * @return string Same string without any accents
     */
    public static function getSortString($inStr)
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
            '@[^a-zA-Z0-9_\-\.\ ]@',
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
        $res = preg_replace($search, $replace, $inStr);

        // Remove double white spaces
        while (str_contains((string) $res, '  ')) {
            $res = str_replace('  ', ' ', $res);
        }

        $res = trim((string) $res, ' -.');

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

        $this->mUuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
