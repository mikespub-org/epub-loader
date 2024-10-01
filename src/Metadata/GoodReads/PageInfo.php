<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class PageInfo
{
    public ?string $typename;
    public ?string $prevPageToken;
    public ?string $nextPageToken;

    public function __construct(
        ?string $typename,
        ?string $prevPageToken,
        ?string $nextPageToken
    ) {
        $this->typename = $typename;
        $this->prevPageToken = $prevPageToken;
        $this->nextPageToken = $nextPageToken;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getPrevPageToken(): ?string
    {
        return $this->prevPageToken;
    }

    public function getNextPageToken(): ?string
    {
        return $this->nextPageToken;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['prevPageToken'] ?? null,
            $data['nextPageToken'] ?? null
        );
    }
}
