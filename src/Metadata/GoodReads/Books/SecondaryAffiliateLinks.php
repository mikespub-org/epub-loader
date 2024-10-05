<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class SecondaryAffiliateLinks
{
    public ?string $typename;
    public ?string $name;
    public ?string $url;
    public ?string $ref;

    public function __construct(
        ?string $typename,
        ?string $name,
        ?string $url,
        ?string $ref
    ) {
        $this->typename = $typename;
        $this->name = $name;
        $this->url = $url;
        $this->ref = $ref;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
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
            $data['__typename'] ?? null,
            $data['name'] ?? null,
            $data['url'] ?? null,
            $data['ref'] ?? null
        );
    }
}
