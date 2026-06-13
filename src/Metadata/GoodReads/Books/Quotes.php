<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

class Quotes
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
        $keys = [
            '__typename' => null,
            'webUrl' => null,
            'totalCount' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
