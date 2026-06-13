<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            'seriesId' => null,
            'seriesBookType' => null,
            'orderNumber' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
