<?php
/**
 * PropCleaner class
 */

namespace Marsender\EPubLoader\Workflows\Converters;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Exception;

/**
 * Clean properties from info
 */
class PropCleaner extends Converter
{
    /** @var array<mixed> */
    protected $clean = [];

    /**
     * Specify properties to clean
     *
     * @param array<string> $clean
     * @throws Exception if error
     */
    public function __construct($clean = ['properties'])
    {
        $this->clean = $clean;
    }

    /**
     * Convert info and/or id
     *
     * @param BookInfo|AuthorInfo|SeriesInfo $info object
     * @param int $id id in the calibre db (or 0 for auto incrementation)
     * @return array{0: BookInfo|AuthorInfo|SeriesInfo, 1: int}
     */
    public function convert($info, $id = 0)
    {
        foreach ($this->clean as $property) {
            if (property_exists($info, $property)) {
                $info->{$property} = null;
            }
        }
        return [$info, $id];
    }
}
