<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

class VolumeSeries
{
    public ?string $seriesId;
    public ?string $seriesBookType;
    public ?int $orderNumber;

    public function __construct(
        ?string $seriesId,
        ?string $seriesBookType,
        ?int $orderNumber
    ) {
        $this->seriesId = $seriesId;
        $this->seriesBookType = $seriesBookType;
        $this->orderNumber = $orderNumber;
    }

    public function getSeriesId(): ?string
    {
        return $this->seriesId;
    }

    public function getSeriesBookType(): ?string
    {
        return $this->seriesBookType;
    }

    public function getOrderNumber(): ?int
    {
        return $this->orderNumber;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['seriesId'] ?? null,
            $data['seriesBookType'] ?? null,
            $data['orderNumber'] ?? null
        );
    }
}
