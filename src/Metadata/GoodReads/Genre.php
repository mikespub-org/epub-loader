<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class Genre
{
    public ?string $typename;
    public ?string $name;
    public ?string $webUrl;

    public function __construct(
        ?string $typename,
        ?string $name,
        ?string $webUrl
    ) {
        $this->typename = $typename;
        $this->name = $name;
        $this->webUrl = $webUrl;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getWebUrl(): ?string
    {
        return $this->webUrl;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['name'] ?? null,
            $data['webUrl'] ?? null
        );
    }
}
