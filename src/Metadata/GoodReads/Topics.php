<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class Topics
{
    public ?string $typename;
    public ?string $webUrl;
    public ?int $totalCount;

    public function __construct(
        ?string $typename,
        ?string $webUrl,
        ?int $totalCount
    ) {
        $this->typename = $typename;
        $this->webUrl = $webUrl;
        $this->totalCount = $totalCount;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getWebUrl(): ?string
    {
        return $this->webUrl;
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['webUrl'] ?? null,
            $data['totalCount'] ?? null
        );
    }
}
