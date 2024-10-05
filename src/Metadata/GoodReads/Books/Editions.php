<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class Editions
{
    public ?string $typename;
    public ?string $webUrl;

    public function __construct(?string $typename, ?string $webUrl)
    {
        $this->typename = $typename;
        $this->webUrl = $webUrl;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
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
            $data['webUrl'] ?? null
        );
    }
}
