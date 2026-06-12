<?php

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Entities;

use Marsender\EPubLoader\Metadata\Mapper;

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
            Mapper::getItem($data, 'author', AuthorKey::fromJson(...)),
            Mapper::getItem($data, 'type', Type::fromJson(...))
        );
    }
}
