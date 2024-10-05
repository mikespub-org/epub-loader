<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class Taggings
{
    public ?string $typename;
    public ?Tag $tag;

    public function __construct(?string $typename, ?Tag $tag)
    {
        $this->typename = $typename;
        $this->tag = $tag;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            ($data['tag'] ?? null) !== null ? Tag::fromJson($data['tag']) : null
        );
    }
}
