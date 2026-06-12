<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

class Shelving
{
    public ?string $typename;
    public ?Shelf $shelf;
    /** @var array<mixed>|null */
    public ?array $taggings;
    public ?string $webUrl;

    /**
     * @param array<mixed>|null $taggings
     */
    public function __construct(
        ?string $typename,
        ?Shelf $shelf,
        ?array $taggings,
        ?string $webUrl
    ) {
        $this->typename = $typename;
        $this->shelf = $shelf;
        $this->taggings = $taggings;
        $this->webUrl = $webUrl;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getShelf(): ?Shelf
    {
        return $this->shelf;
    }

    /**
     * @return array<mixed>|null
     */
    public function getTaggings(): ?array
    {
        return $this->taggings;
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
            Mapper::getItem($data, 'shelf', Shelf::fromJson(...)),
            $data['taggings'] ?? null,
            $data['webUrl'] ?? null
        );
    }
}
