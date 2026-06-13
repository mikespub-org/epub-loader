<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            '__typename' => null,
            'prevPageToken' => null,
            'nextPageToken' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
