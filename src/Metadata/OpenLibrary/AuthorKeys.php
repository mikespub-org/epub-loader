<?php

namespace Marsender\EPubLoader\Metadata\OpenLibrary;

class AuthorKeys
{
    public ?Type $type;
    public ?string $author;

    public function __construct(?Type $type, ?string $author)
    {
        $this->type = $type;
        $this->author = $author;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            ($data['type'] ?? null) !== null ? Type::fromJson($data['type']) : null,
            $data['author'] ?? null
        );
    }
}
