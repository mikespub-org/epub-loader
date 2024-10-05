<?php
/**
 * Adapted to include seriesHeader from SeriesList
 */

namespace Marsender\EPubLoader\Metadata\GoodReads\Series;

class Book
{
    public ?string $imageUrl;
    public ?string $bookId;
    public ?string $workId;
    public ?string $bookUrl;
    public ?bool $fromSearch;
    public ?bool $fromSrp;
    public mixed $qid;
    public mixed $rank;
    public ?string $title;
    public ?string $bookTitleBare;
    public ?int $numPages;
    public ?float $avgRating;
    public ?int $ratingsCount;
    public ?Author $author;
    public mixed $kcrPreviewUrl;
    public ?Description $description;
    public ?int $textReviewsCount;
    public ?string $publicationDate;
    public ?bool $toBePublished;
    public ?string $editions;
    public ?string $editionsUrl;
    public ?string $seriesHeader;

    public function __construct(
        ?string $imageUrl,
        ?string $bookId,
        ?string $workId,
        ?string $bookUrl,
        ?bool $fromSearch,
        ?bool $fromSrp,
        mixed $qid,
        mixed $rank,
        ?string $title,
        ?string $bookTitleBare,
        ?int $numPages,
        ?float $avgRating,
        ?int $ratingsCount,
        ?Author $author,
        mixed $kcrPreviewUrl,
        ?Description $description,
        ?int $textReviewsCount,
        ?string $publicationDate,
        ?bool $toBePublished,
        ?string $editions,
        ?string $editionsUrl,
        ?string $seriesHeader,
    ) {
        $this->imageUrl = $imageUrl;
        $this->bookId = $bookId;
        $this->workId = $workId;
        $this->bookUrl = $bookUrl;
        $this->fromSearch = $fromSearch;
        $this->fromSrp = $fromSrp;
        $this->qid = $qid;
        $this->rank = $rank;
        $this->title = $title;
        $this->bookTitleBare = $bookTitleBare;
        $this->numPages = $numPages;
        $this->avgRating = $avgRating;
        $this->ratingsCount = $ratingsCount;
        $this->author = $author;
        $this->kcrPreviewUrl = $kcrPreviewUrl;
        $this->description = $description;
        $this->textReviewsCount = $textReviewsCount;
        $this->publicationDate = $publicationDate;
        $this->toBePublished = $toBePublished;
        $this->editions = $editions;
        $this->editionsUrl = $editionsUrl;
        $this->seriesHeader = $seriesHeader;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getBookId(): ?string
    {
        return $this->bookId;
    }

    public function getWorkId(): ?string
    {
        return $this->workId;
    }

    public function getBookUrl(): ?string
    {
        return $this->bookUrl;
    }

    public function getFromSearch(): ?bool
    {
        return $this->fromSearch;
    }

    public function getFromSrp(): ?bool
    {
        return $this->fromSrp;
    }

    public function getQid(): mixed
    {
        return $this->qid;
    }

    public function getRank(): mixed
    {
        return $this->rank;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getBookTitleBare(): ?string
    {
        return $this->bookTitleBare;
    }

    public function getNumPages(): ?int
    {
        return $this->numPages;
    }

    public function getAvgRating(): ?float
    {
        return $this->avgRating;
    }

    public function getRatingsCount(): ?int
    {
        return $this->ratingsCount;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function getKcrPreviewUrl(): mixed
    {
        return $this->kcrPreviewUrl;
    }

    public function getDescription(): ?Description
    {
        return $this->description;
    }

    public function getTextReviewsCount(): ?int
    {
        return $this->textReviewsCount;
    }

    public function getPublicationDate(): ?string
    {
        return $this->publicationDate;
    }

    public function getToBePublished(): ?bool
    {
        return $this->toBePublished;
    }

    public function getEditions(): ?string
    {
        return $this->editions;
    }

    public function getEditionsUrl(): ?string
    {
        return $this->editionsUrl;
    }

    public function getSeriesHeader(): ?string
    {
        return $this->seriesHeader;
    }

    public function setSeriesHeader(?string $seriesHeader): void
    {
        $this->seriesHeader = $seriesHeader;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['imageUrl'] ?? null,
            $data['bookId'] ?? null,
            $data['workId'] ?? null,
            $data['bookUrl'] ?? null,
            $data['from_search'] ?? null,
            $data['from_srp'] ?? null,
            $data['qid'] ?? null,
            $data['rank'] ?? null,
            $data['title'] ?? null,
            $data['bookTitleBare'] ?? null,
            $data['numPages'] ?? null,
            $data['avgRating'] ?? null,
            $data['ratingsCount'] ?? null,
            ($data['author'] ?? null) !== null ? Author::fromJson($data['author']) : null,
            $data['kcrPreviewUrl'] ?? null,
            ($data['description'] ?? null) !== null ? Description::fromJson($data['description']) : null,
            $data['textReviewsCount'] ?? null,
            $data['publicationDate'] ?? null,
            $data['toBePublished'] ?? null,
            $data['editions'] ?? null,
            $data['editionsUrl'] ?? null,
            $data['seriesHeader'] ?? null
        );
    }
}
