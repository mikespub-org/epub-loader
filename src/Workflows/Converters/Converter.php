<?php
/**
 * Converter class
 */

namespace Marsender\EPubLoader\Workflows\Converters;

/**
 * Convert x to y
 */
abstract class Converter
{
    /**
     * Summary of getBookId
     * @param string $bookFileName
     * @return int
     */
    abstract public function getBookId($bookFileName);
}
