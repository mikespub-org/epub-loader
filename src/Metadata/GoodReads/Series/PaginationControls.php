<?php

/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 */

namespace Marsender\EPubLoader\Metadata\GoodReads\Series;

use Marsender\EPubLoader\Metadata\Mapper;

class PaginationControls
{
    public ?int $numWorks;
    public ?int $currentPageNumber;
    public ?int $perPage;

    public function __construct(
        ?int $numWorks,
        ?int $currentPageNumber,
        ?int $perPage
    ) {
        $this->numWorks = $numWorks;
        $this->currentPageNumber = $currentPageNumber;
        $this->perPage = $perPage;
    }

    public function getNumWorks(): ?int
    {
        return $this->numWorks;
    }

    public function getCurrentPageNumber(): ?int
    {
        return $this->currentPageNumber;
    }

    public function getPerPage(): ?int
    {
        return $this->perPage;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            'numWorks' => null,
            'currentPageNumber' => null,
            'perPage' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
