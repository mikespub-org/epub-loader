<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Search;

class Books
{
    public ?string $id;
    public ?string $title;
    public ?string $cover;
    public float|int|null $rating;
    public ?int $count;

    public function __construct(
        ?string $id,
        ?string $title,
        ?string $cover,
        float|int|null $rating,
        ?int $count
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->cover = $cover;
        $this->rating = $rating;
        $this->count = $count;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function getRating(): float|int|null
    {
        return $this->rating;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['title'] ?? null,
            $data['cover'] ?? null,
            $data['rating'] ?? null,
            $data['count'] ?? null
        );
    }
}
