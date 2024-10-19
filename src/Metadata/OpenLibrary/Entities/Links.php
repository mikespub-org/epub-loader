<?php

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Entities;

class Links
{
    public ?string $url;
    public ?string $title;
    public ?Type $type;

    public function __construct(
        ?string $url,
        ?string $title,
        ?Type $type
    ) {
        $this->url = $url;
        $this->title = $title;
        $this->type = $type;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['url'] ?? null,
            $data['title'] ?? null,
            ($data['type'] ?? null) !== null ? Type::fromJson($data['type']) : null
        );
    }
}
