<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

class BookSeries
{
    public ?string $typename;
    public ?string $userPosition;
    public ?Series $series;

    public function __construct(
        ?string $typename,
        ?string $userPosition,
        ?Series $series
    ) {
        $this->typename = $typename;
        $this->userPosition = $userPosition;
        $this->series = $series;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getUserPosition(): ?string
    {
        return $this->userPosition;
    }

    public function getSeries(): ?Series
    {
        return $this->series;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            '__typename' => null,
            'userPosition' => null,
            'series' => Series::fromJson(...),
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
