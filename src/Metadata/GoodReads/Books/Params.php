<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

class Params
{
    public ?string $bookId;

    public function __construct(?string $bookId)
    {
        $this->bookId = $bookId;
    }

    public function getBookId(): ?string
    {
        return $this->bookId;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            'book_id' => null, // Normalizes to bookId
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
