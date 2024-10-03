<?php
/**
 * OpenLibraryWork import class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\BookInfos;
use Marsender\EPubLoader\Metadata\OpenLibrary\WorkEntity;
use Exception;

class OpenLibraryWork
{
    /**
     * Parse JSON data for an OpenLibrary work
     *
     * @param array<mixed> $data
     *
     * @return WorkEntity
     */
    public static function parse($data)
    {
        $work = WorkEntity::fromJson($data);
        return $work;
    }

    /**
     * Loads book infos from an OpenLibrary work
     *
     * @param string $inBasePath base directory
     * @param WorkEntity $work OpenLibrary work
     * @throws Exception if error
     *
     * @return BookInfos
     */
    public static function load($inBasePath, $work)
    {
        $bookInfos = new BookInfos();
        $bookInfos->mBasePath = $inBasePath;
        // @todo ...

        return $bookInfos;
    }

    /**
     * Summary of import
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfos
     */
    public static function import($dbPath, $data)
    {
        $work = static::parse($data);
        return static::load($dbPath, $work);
    }
}
