<?php

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Entities;

class WorkKey
{
    public ?string $key;

    public function __construct(?string $key)
    {
        $this->key = $key;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['key'] ?? null
        );
    }
}
