<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

class FeaturedKnh
{
    public ?string $typename;
    public ?int $totalCount;
    /** @var array<mixed>|null */
    public ?array $edges;

    /**
     * @param array<mixed>|null $edges
     */
    public function __construct(
        ?string $typename,
        ?int $totalCount,
        ?array $edges
    ) {
        $this->typename = $typename;
        $this->totalCount = $totalCount;
        $this->edges = $edges;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    /**
     * @return array<mixed>|null
     */
    public function getEdges(): ?array
    {
        return $this->edges;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            '__typename' => null,
            'totalCount' => null,
            'edges' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
