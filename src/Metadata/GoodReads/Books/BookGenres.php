<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class BookGenres
{
    public ?string $typename;
    public ?Genre $genre;

    public function __construct(?string $typename, ?Genre $genre)
    {
        $this->typename = $typename;
        $this->genre = $genre;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getGenre(): ?Genre
    {
        return $this->genre;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            ($data['genre'] ?? null) !== null ? Genre::fromJson($data['genre']) : null
        );
    }
}
