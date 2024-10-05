<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class SeriesMap
{
    public ?string $id;
    public ?string $typename;
    public ?string $title;
    public ?string $webUrl;

    public function __construct(
        ?string $id,
        ?string $typename,
        ?string $title,
        ?string $webUrl
    ) {
        $this->id = $id;
        $this->typename = $typename;
        $this->title = $title;
        $this->webUrl = $webUrl;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getTitle(): ?string
    {
        return $this->title;
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
            $data['id'] ?? null,
            $data['__typename'] ?? null,
            $data['title'] ?? null,
            $data['webUrl'] ?? null
        );
    }
}
