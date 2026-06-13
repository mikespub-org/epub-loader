<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            '__typename' => null,
            'genre' => Genre::fromJson(...),
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
