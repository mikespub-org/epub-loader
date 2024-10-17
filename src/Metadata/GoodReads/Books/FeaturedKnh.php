<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

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
        return new self(
            $data['__typename'] ?? null,
            $data['totalCount'] ?? null,
            $data['edges'] ?? null
        );
    }
}
