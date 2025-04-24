<?php

/**
 * IdMapper class
 */

namespace Marsender\EPubLoader\Workflows\Converters;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Exception;

/**
 * Map bookId to filename
 */
class IdMapper extends Converter
{
    /** @var array<mixed> */
    protected $authors = [];
    /** @var array<mixed> */
    protected $books = [];
    /** @var array<mixed> */
    protected $series = [];
    /** @var array<mixed> */
    protected $stats = [];
    protected bool $override = false;

    /**
     * Initialize mapper if needed
     *
     * @param bool $override
     */
    public function __construct($override = false)
    {
        $this->override = $override;
        $this->stats['hit'] ??= 0;
        $this->stats['miss'] ??= 0;
    }

    /**
     * Get id from info for books
     * @param BookInfo $info
     * @return int id
     */
    public function getBookId($info)
    {
        if (!empty($info->id) && array_key_exists($info->id, $this->books)) {
            $this->stats['hit'] += 1;
            // @todo set calibreid + go through authors & series here?
            return $this->books[$info->id];
        }
        $this->stats['miss'] += 1;
        return 0;
    }

    /**
     * Get id from info for authors
     * @param AuthorInfo $info
     * @return int id
     */
    public function getAuthorId($info)
    {
        if (!empty($info->id) && array_key_exists($info->id, $this->authors)) {
            $this->stats['hit'] += 1;
            // @todo set calibreid + go through books & series here?
            return $this->authors[$info->id];
        }
        $this->stats['miss'] += 1;
        return 0;
    }

    /**
     * Get id from info for series
     * @param SeriesInfo $info
     * @return int id
     */
    public function getSeriesId($info)
    {
        if (!empty($info->id) && array_key_exists($info->id, $this->series)) {
            $this->stats['hit'] += 1;
            // @todo set calibreid + go through authors & books here?
            return $this->series[$info->id];
        }
        $this->stats['miss'] += 1;
        return 0;
    }

    /**
     * Summary of getStats
     * @return array<mixed>
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Convert info and/or id
     *
     * @param BookInfo|AuthorInfo|SeriesInfo $info object
     * @param int $id id in the calibre db (or 0 for auto incrementation)
     * @throws Exception if error
     * @return array{0: BookInfo|AuthorInfo|SeriesInfo, 1: int}
     */
    public function convert($info, $id = 0)
    {
        if (!empty($id) && !$this->override) {
            return [$info, $id];
        }
        switch ($info::class) {
            case BookInfo::class:
                $id = $this->getBookId($info);
                break;
            case AuthorInfo::class:
                $id = $this->getAuthorId($info);
                break;
            case SeriesInfo::class:
                $id = $this->getSeriesId($info);
                break;
            default:
                $error = sprintf('Incorrect info type: %s', $info::class);
                throw new Exception($error);
        }
        return [$info, $id];
    }
}
