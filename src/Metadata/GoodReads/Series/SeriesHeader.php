<?php
/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 */

namespace Marsender\EPubLoader\Metadata\GoodReads\Series;

class SeriesHeader
{
    public ?string $title;
    public ?string $subtitle;
    public ?Description $description;

    public function __construct(
        ?string $title,
        ?string $subtitle,
        ?Description $description
    ) {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->description = $description;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function getDescription(): ?Description
    {
        return $this->description;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['title'] ?? null,
            $data['subtitle'] ?? null,
            ($data['description'] ?? null) !== null ? Description::fromJson($data['description']) : null
        );
    }
}
