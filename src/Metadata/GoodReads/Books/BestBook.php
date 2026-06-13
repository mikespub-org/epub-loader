<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

class BestBook
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
        $keys = [
            '__ref' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
