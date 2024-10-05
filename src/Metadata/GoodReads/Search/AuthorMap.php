<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Search;

class AuthorMap
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
        return new self(
            $data['id'] ?? null,
            $data['name'] ?? null,
            ($data['books'] ?? null) !== null ? array_map(static function ($data) {
                return Books::fromJson($data);
            }, $data['books']) : null
        );
    }
}
