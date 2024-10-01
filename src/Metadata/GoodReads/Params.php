<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

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
        return new self(
            $data['book_id'] ?? null
        );
    }
}
