<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class Stats
{
    public ?string $typename;
    public ?float $averageRating;
    public ?int $ratingsCount;
    /** @var int[]|null */
    public ?array $ratingsCountDist;
    public ?int $textReviewsCount;
    /** @var TextReviewsLanguageCounts[]|null */
    public ?array $textReviewsLanguageCounts;

    /**
     * @param int[]|null $ratingsCountDist
     * @param TextReviewsLanguageCounts[]|null $textReviewsLanguageCounts
     */
    public function __construct(
        ?string $typename,
        ?float $averageRating,
        ?int $ratingsCount,
        ?array $ratingsCountDist,
        ?int $textReviewsCount,
        ?array $textReviewsLanguageCounts
    ) {
        $this->typename = $typename;
        $this->averageRating = $averageRating;
        $this->ratingsCount = $ratingsCount;
        $this->ratingsCountDist = $ratingsCountDist;
        $this->textReviewsCount = $textReviewsCount;
        $this->textReviewsLanguageCounts = $textReviewsLanguageCounts;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getAverageRating(): ?float
    {
        return $this->averageRating;
    }

    public function getRatingsCount(): ?int
    {
        return $this->ratingsCount;
    }

    /**
     * @return int[]|null
     */
    public function getRatingsCountDist(): ?array
    {
        return $this->ratingsCountDist;
    }

    public function getTextReviewsCount(): ?int
    {
        return $this->textReviewsCount;
    }

    /**
     * @return TextReviewsLanguageCounts[]|null
     */
    public function getTextReviewsLanguageCounts(): ?array
    {
        return $this->textReviewsLanguageCounts;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['averageRating'] ?? null,
            $data['ratingsCount'] ?? null,
            $data['ratingsCountDist'] ?? null,
            $data['textReviewsCount'] ?? null,
            ($data['textReviewsLanguageCounts'] ?? null) !== null ? array_map(static function ($data) {
                return TextReviewsLanguageCounts::fromJson($data);
            }, $data['textReviewsLanguageCounts']) : null
        );
    }
}
