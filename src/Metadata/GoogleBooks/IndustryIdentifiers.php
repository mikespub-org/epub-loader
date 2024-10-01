<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

class IndustryIdentifiers
{
    public ?string $type;
    public ?string $identifier;

    public function __construct(?string $type, ?string $identifier)
    {
        $this->type = $type;
        $this->identifier = $identifier;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['type'] ?? null,
            $data['identifier'] ?? null
        );
    }
}
