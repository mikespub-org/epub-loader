<?php
/**
 * HasSeriesTrait trait
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Models;

/**
 * Deal with series property
 */
trait HasSeriesTrait
{
    /** @var array<SeriesInfo> */
    public array $series = [];

    /**
     * Summary of addSeries
     * @param mixed $seriesId
     * @param array<mixed> $info
     * @return SeriesInfo
     */
    public function addSeries($seriesId, $info)
    {
        $seriesInfo = SeriesInfo::load($this->basePath, $info);
        if (empty($seriesId)) {
            $seriesId = count($this->series);
        }
        $this->series[$seriesId] = $seriesInfo;
        return $seriesInfo;
    }

    /**
     * Get the first series or an empty one (for import/export)
     * @return SeriesInfo
     */
    public function getSeriesInfo()
    {
        $seriesInfo = reset($this->series);
        if (!$seriesInfo) {
            $seriesInfo = new SeriesInfo();
        }
        return $seriesInfo;
    }

    /**
     * Set titles for series
     * @param array<string> $seriesList
     * @return self
     */
    public function setSeriesTitles($seriesList)
    {
        foreach ($this->series as $id => $series) {
            if (empty($series->id) ||
                $series->id != $series->title ||
                empty($seriesList[$series->id])) {
                continue;
            }
            $series->title = $seriesList[$series->id];
            $series->sort = SeriesInfo::getTitleSort($series->title);
            $this->series[$id] = $series;
        }
        return $this;
    }

    /**
     * Set names for authors & titles for books in series
     * @param array<string> $authorList
     * @param array<string> $bookList
     * @return self
     */
    public function fixSeries($authorList, $bookList = [])
    {
        foreach ($this->series as $id => $series) {
            $series->setAuthorNames($authorList);
            $series->setBookTitles($bookList);
            $this->series[$id] = $series;
        }
        return $this;
    }
}
