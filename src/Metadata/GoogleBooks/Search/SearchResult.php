<?php

/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 */

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Search;

use Marsender\EPubLoader\Metadata\GoogleBooks\Volumes\Volume;

class SearchResult
{
    public ?string $kind;
    public ?int $totalItems;
    /** @var Volume[]|null */
    public ?array $items;

    /**
     * @param Volume[]|null $items
     */
    public function __construct(
        ?string $kind,
        ?int $totalItems,
        ?array $items
    ) {
        $this->kind = $kind;
        $this->totalItems = $totalItems;
        $this->items = $items;
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function getTotalItems(): ?int
    {
        return $this->totalItems;
    }

    /**
     * @return Volume[]|null
     */
    public function getItems(): ?array
    {
        return $this->items;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['kind'] ?? null,
            $data['totalItems'] ?? null,
            ($data['items'] ?? null) !== null ? array_map(static function ($data) {
                return Volume::fromJson($data);
            }, $data['items']) : null
        );
    }
}
