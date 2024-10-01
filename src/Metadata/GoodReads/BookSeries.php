<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

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
        return new self(
            $data['__typename'] ?? null,
            $data['userPosition'] ?? null,
            ($data['series'] ?? null) !== null ? Series::fromJson($data['series']) : null
        );
    }
}
