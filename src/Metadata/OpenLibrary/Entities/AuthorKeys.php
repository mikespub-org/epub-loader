<?php

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Entities;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            'type' => Type::fromJson(...),
            'author' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
