<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Series;

use Marsender\EPubLoader\Metadata\Mapper;

class Series
{
    public ?bool $isLibrarianView;
    public ?Book $book;

    public function __construct(?bool $isLibrarianView, ?Book $book)
    {
        $this->isLibrarianView = $isLibrarianView;
        $this->book = $book;
    }

    public function getIsLibrarianView(): ?bool
    {
        return $this->isLibrarianView;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            'isLibrarianView' => null,
            'book' => Book::fromJson(...),
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
