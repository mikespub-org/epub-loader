<?php

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Search;

class WorkDocs
{
    public ?string $key;
    public ?string $type;
    public ?string $title;
    public ?int $editionCount;
    public ?int $firstPublishYear;
    public ?int $numberOfPagesMedian;
    /** @var string[]|null */
    public ?array $authorKey;
    /** @var string[]|null */
    public ?array $authorName;

    /**
     * @param string[]|null $authorKey
     * @param string[]|null $authorName
     */
    public function __construct(
        ?string $key,
        ?string $type,
        ?string $title,
        ?int $editionCount,
        ?int $firstPublishYear,
        ?int $numberOfPagesMedian,
        ?array $authorKey,
        ?array $authorName
    ) {
        $this->key = $key;
        $this->type = $type;
        $this->title = $title;
        $this->editionCount = $editionCount;
        $this->firstPublishYear = $firstPublishYear;
        $this->numberOfPagesMedian = $numberOfPagesMedian;
        $this->authorKey = $authorKey;
        $this->authorName = $authorName;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getEditionCount(): ?int
    {
        return $this->editionCount;
    }

    public function getFirstPublishYear(): ?int
    {
        return $this->firstPublishYear;
    }

    public function getNumberOfPagesMedian(): ?int
    {
        return $this->numberOfPagesMedian;
    }

    /**
     * @return string[]|null
     */
    public function getAuthorKey(): ?array
    {
        return $this->authorKey;
    }

    /**
     * @return string[]|null
     */
    public function getAuthorName(): ?array
    {
        return $this->authorName;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['key'] ?? null,
            $data['type'] ?? null,
            $data['title'] ?? null,
            $data['edition_count'] ?? null,
            $data['first_publish_year'] ?? null,
            $data['number_of_pages_median'] ?? null,
            $data['author_key'] ?? null,
            $data['author_name'] ?? null
        );
    }
}
