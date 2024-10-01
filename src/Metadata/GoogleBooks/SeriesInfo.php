<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

class SeriesInfo
{
    public ?string $kind;
    public ?string $shortSeriesBookTitle;
    public ?string $bookDisplayNumber;
    /** @var VolumeSeries[]|null */
    public ?array $volumeSeries;

    /**
     * @param VolumeSeries[]|null $volumeSeries
     */
    public function __construct(
        ?string $kind,
        ?string $shortSeriesBookTitle,
        ?string $bookDisplayNumber,
        ?array $volumeSeries
    ) {
        $this->kind = $kind;
        $this->shortSeriesBookTitle = $shortSeriesBookTitle;
        $this->bookDisplayNumber = $bookDisplayNumber;
        $this->volumeSeries = $volumeSeries;
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function getShortSeriesBookTitle(): ?string
    {
        return $this->shortSeriesBookTitle;
    }

    public function getBookDisplayNumber(): ?string
    {
        return $this->bookDisplayNumber;
    }

    /**
     * @return VolumeSeries[]|null
     */
    public function getVolumeSeries(): ?array
    {
        return $this->volumeSeries;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['kind'] ?? null,
            $data['shortSeriesBookTitle'] ?? null,
            $data['bookDisplayNumber'] ?? null,
            ($data['volumeSeries'] ?? null) !== null ? array_map(static function ($data) {
                return VolumeSeries::fromJson($data);
            }, $data['volumeSeries']) : null
        );
    }
}
