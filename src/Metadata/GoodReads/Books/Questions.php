<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            '__typename' => null,
            'totalCount' => null,
            'webUrl' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
