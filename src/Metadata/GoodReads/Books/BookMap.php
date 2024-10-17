<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class BookMap
{
    public ?string $id;
    public ?string $typename;
    public ?string $title;
    public ?string $titleComplete;
    public ?int $legacyId;
    public ?string $webUrl;
    public ?string $description;
    public ?string $descriptionStrippedTrue;
    public ?PrimaryContributorEdge $primaryContributorEdge;
    /** @var array<mixed>|null */
    public ?array $secondaryContributorEdges;
    public ?string $imageUrl;
    /** @var BookSeries[]|null */
    public ?array $bookSeries;
    /** @var BookGenres[]|null */
    public ?array $bookGenres;
    public ?Details $details;
    public ?Work $work;
    public ?string $reviewEditUrl;
    public ?FeatureFlags $featureFlags;
    public mixed $viewerShelving;
    public ?Links $links;

    /**
     * @param array<mixed>|null $secondaryContributorEdges
     * @param BookSeries[]|null $bookSeries
     * @param BookGenres[]|null $bookGenres
     */
    public function __construct(
        ?string $id,
        ?string $typename,
        ?string $title,
        ?string $titleComplete,
        ?int $legacyId,
        ?string $webUrl,
        ?string $description,
        ?string $descriptionStrippedTrue,
        ?PrimaryContributorEdge $primaryContributorEdge,
        ?array $secondaryContributorEdges,
        ?string $imageUrl,
        ?array $bookSeries,
        ?array $bookGenres,
        ?Details $details,
        ?Work $work,
        ?string $reviewEditUrl,
        ?FeatureFlags $featureFlags,
        mixed $viewerShelving,
        ?Links $links
    ) {
        $this->id = $id;
        $this->typename = $typename;
        $this->title = $title;
        $this->titleComplete = $titleComplete;
        $this->legacyId = $legacyId;
        $this->webUrl = $webUrl;
        $this->description = $description;
        $this->descriptionStrippedTrue = $descriptionStrippedTrue;
        $this->primaryContributorEdge = $primaryContributorEdge;
        $this->secondaryContributorEdges = $secondaryContributorEdges;
        $this->imageUrl = $imageUrl;
        $this->bookSeries = $bookSeries;
        $this->bookGenres = $bookGenres;
        $this->details = $details;
        $this->work = $work;
        $this->reviewEditUrl = $reviewEditUrl;
        $this->featureFlags = $featureFlags;
        $this->viewerShelving = $viewerShelving;
        $this->links = $links;
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

    public function getTitleComplete(): ?string
    {
        return $this->titleComplete;
    }

    public function getLegacyId(): ?int
    {
        return $this->legacyId;
    }

    public function getWebUrl(): ?string
    {
        return $this->webUrl;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDescriptionStrippedTrue(): ?string
    {
        return $this->descriptionStrippedTrue;
    }

    public function getPrimaryContributorEdge(): ?PrimaryContributorEdge
    {
        return $this->primaryContributorEdge;
    }

    /**
     * @return array<mixed>|null
     */
    public function getSecondaryContributorEdges(): ?array
    {
        return $this->secondaryContributorEdges;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * @return BookSeries[]|null
     */
    public function getBookSeries(): ?array
    {
        return $this->bookSeries;
    }

    /**
     * @return BookGenres[]|null
     */
    public function getBookGenres(): ?array
    {
        return $this->bookGenres;
    }

    public function getDetails(): ?Details
    {
        return $this->details;
    }

    public function getWork(): ?Work
    {
        return $this->work;
    }

    public function getReviewEditUrl(): ?string
    {
        return $this->reviewEditUrl;
    }

    public function getFeatureFlags(): ?FeatureFlags
    {
        return $this->featureFlags;
    }

    public function getViewerShelving(): mixed
    {
        return $this->viewerShelving;
    }

    public function getLinks(): ?Links
    {
        return $this->links;
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
            $data['titleComplete'] ?? null,
            $data['legacyId'] ?? null,
            $data['webUrl'] ?? null,
            $data['description'] ?? null,
            $data['description({"stripped":true})'] ?? null,
            ($data['primaryContributorEdge'] ?? null) !== null ? PrimaryContributorEdge::fromJson($data['primaryContributorEdge']) : null,
            $data['secondaryContributorEdges'] ?? null,
            $data['imageUrl'] ?? null,
            ($data['bookSeries'] ?? null) !== null ? array_map(static function ($data) {
                return BookSeries::fromJson($data);
            }, $data['bookSeries']) : null,
            ($data['bookGenres'] ?? null) !== null ? array_map(static function ($data) {
                return BookGenres::fromJson($data);
            }, $data['bookGenres']) : null,
            ($data['details'] ?? null) !== null ? Details::fromJson($data['details']) : null,
            ($data['work'] ?? null) !== null ? Work::fromJson($data['work']) : null,
            $data['reviewEditUrl'] ?? null,
            ($data['featureFlags'] ?? null) !== null ? FeatureFlags::fromJson($data['featureFlags']) : null,
            $data['viewerShelving'] ?? null,
            ($data['links({})'] ?? null) !== null ? Links::fromJson($data['links({})']) : null
        );
    }
}
