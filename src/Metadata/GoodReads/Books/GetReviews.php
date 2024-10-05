<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class GetReviews
{
    public ?string $typename;
    public ?int $totalCount;
    /** @var Edges[]|null */
    public ?array $edges;
    public ?PageInfo $pageInfo;

    /**
     * @param Edges[]|null $edges
     */
    public function __construct(
        ?string $typename,
        ?int $totalCount,
        ?array $edges,
        ?PageInfo $pageInfo
    ) {
        $this->typename = $typename;
        $this->totalCount = $totalCount;
        $this->edges = $edges;
        $this->pageInfo = $pageInfo;
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
     * @return Edges[]|null
     */
    public function getEdges(): ?array
    {
        return $this->edges;
    }

    public function getPageInfo(): ?PageInfo
    {
        return $this->pageInfo;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['totalCount'] ?? null,
            ($data['edges'] ?? null) !== null ? array_map(static function ($data) {
                return Edges::fromJson($data);
            }, $data['edges']) : null,
            ($data['pageInfo'] ?? null) !== null ? PageInfo::fromJson($data['pageInfo']) : null
        );
    }
}
