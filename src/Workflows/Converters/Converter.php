<?php

/**
 * Converter class
 * |-> IdMapper
 * |-> PropCleaner
 * |-> ...
 * implement convert()
 */

namespace Marsender\EPubLoader\Workflows\Converters;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;

/**
 * Convert info and/or id
 */
abstract class Converter
{
    /**
     * Convert info and/or id
     *
     * @param BookInfo|AuthorInfo|SeriesInfo $info object
     * @param int $id id in the calibre db (or 0 for auto incrementation)
     * @return array{0: BookInfo|AuthorInfo|SeriesInfo, 1: int}
     */
    abstract public function convert($info, $id = 0);
}
