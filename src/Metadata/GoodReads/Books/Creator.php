<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class Creator
{
    public ?string $ref;

    public function __construct(?string $ref)
    {
        $this->ref = $ref;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__ref'] ?? null
        );
    }
}
