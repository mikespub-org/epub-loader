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
        $keys = [
            'author' => AuthorKey::fromJson(...),
            'type' => Type::fromJson(...),
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
