<?php

namespace Marsender\EPubLoader\Metadata\OpenLibrary;

class Authors
{
    public ?AuthorKey $author;
    public ?Type $type;

    public function __construct(?AuthorKey $author, ?Type $type)
    {
        $this->author = $author;
        $this->type = $type;
    }

    public function getAuthor(): ?AuthorKey
    {
        return $this->author;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            ($data['author'] ?? null) !== null ? AuthorKey::fromJson($data['author']) : null,
            ($data['type'] ?? null) !== null ? Type::fromJson($data['type']) : null
        );
    }
}
