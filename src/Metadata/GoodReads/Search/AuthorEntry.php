<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Search;

use Marsender\EPubLoader\Metadata\Mapper;

class AuthorEntry
{
    public ?string $id;
    public ?string $name;
    /** @var Books[]|null */
    public ?array $books;

    /**
     * @param Books[]|null $books
     */
    public function __construct(
        ?string $id,
        ?string $name,
        ?array $books
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->books = $books;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return Books[]|null
     */
    public function getBooks(): ?array
    {
        return $this->books;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            'id' => null,
            'name' => null,
            'books' => [ Books::fromJson(...) ],
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
