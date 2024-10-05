<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class Questions
{
    public ?string $typename;
    public ?int $totalCount;
    public ?string $webUrl;

    public function __construct(
        ?string $typename,
        ?int $totalCount,
        ?string $webUrl
    ) {
        $this->typename = $typename;
        $this->totalCount = $totalCount;
        $this->webUrl = $webUrl;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    public function getWebUrl(): ?string
    {
        return $this->webUrl;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['totalCount'] ?? null,
            $data['webUrl'] ?? null
        );
    }
}
