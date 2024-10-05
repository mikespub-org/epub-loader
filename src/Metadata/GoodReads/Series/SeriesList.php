<?php
/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 */

namespace Marsender\EPubLoader\Metadata\GoodReads\Series;

class SeriesList
{
    /** @var Series[]|null */
    public ?array $series;
    /** @var string[]|null */
    public ?array $seriesHeaders;

    /**
     * @param Series[]|null $series
     * @param string[]|null $seriesHeaders
     */
    public function __construct(?array $series, ?array $seriesHeaders)
    {
        $this->series = $series;
        $this->seriesHeaders = $seriesHeaders;
    }

    /**
     * @return Series[]|null
     */
    public function getSeries(): ?array
    {
        return $this->series;
    }

    /**
     * @return string[]|null
     */
    public function getSeriesHeaders(): ?array
    {
        return $this->seriesHeaders;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            ($data['series'] ?? null) !== null ? array_map(static function ($data) {
                return Series::fromJson($data);
            }, $data['series']) : null,
            $data['seriesHeaders'] ?? null
        );
    }
}
