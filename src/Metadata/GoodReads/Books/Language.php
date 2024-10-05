<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class Language
{
    public ?string $typename;
    public ?string $name;

    public function __construct(?string $typename, ?string $name)
    {
        $this->typename = $typename;
        $this->name = $name;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['name'] ?? null
        );
    }
}
