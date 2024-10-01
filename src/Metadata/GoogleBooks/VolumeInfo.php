<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

class VolumeInfo
{
    public ?string $title;
    /** @var string[]|null */
    public ?array $authors;
    public ?string $publisher;
    public ?string $publishedDate;
    public ?string $description;
    /** @var IndustryIdentifiers[]|null */
    public ?array $industryIdentifiers;
    public ?ReadingModes $readingModes;
    public ?int $pageCount;
    public ?string $printType;
    /** @var string[]|null */
    public ?array $categories;
    public ?string $maturityRating;
    public ?bool $allowAnonLogging;
    public ?string $contentVersion;
    public ?PanelizationSummary $panelizationSummary;
    public ?ImageLinks $imageLinks;
    public ?string $language;
    public ?string $previewLink;
    public ?string $infoLink;
    public ?string $canonicalVolumeLink;
    public ?string $subtitle;
    public float|int|null $averageRating;
    public ?int $ratingsCount;
    public ?SeriesInfo $seriesInfo;

    /**
     * @param string[]|null $authors
     * @param IndustryIdentifiers[]|null $industryIdentifiers
     * @param string[]|null $categories
     */
    public function __construct(
        ?string $title,
        ?array $authors,
        ?string $publisher,
        ?string $publishedDate,
        ?string $description,
        ?array $industryIdentifiers,
        ?ReadingModes $readingModes,
        ?int $pageCount,
        ?string $printType,
        ?array $categories,
        ?string $maturityRating,
        ?bool $allowAnonLogging,
        ?string $contentVersion,
        ?PanelizationSummary $panelizationSummary,
        ?ImageLinks $imageLinks,
        ?string $language,
        ?string $previewLink,
        ?string $infoLink,
        ?string $canonicalVolumeLink,
        ?string $subtitle,
        float|int|null $averageRating,
        ?int $ratingsCount,
        ?SeriesInfo $seriesInfo
    ) {
        $this->title = $title;
        $this->authors = $authors;
        $this->publisher = $publisher;
        $this->publishedDate = $publishedDate;
        $this->description = $description;
        $this->industryIdentifiers = $industryIdentifiers;
        $this->readingModes = $readingModes;
        $this->pageCount = $pageCount;
        $this->printType = $printType;
        $this->categories = $categories;
        $this->maturityRating = $maturityRating;
        $this->allowAnonLogging = $allowAnonLogging;
        $this->contentVersion = $contentVersion;
        $this->panelizationSummary = $panelizationSummary;
        $this->imageLinks = $imageLinks;
        $this->language = $language;
        $this->previewLink = $previewLink;
        $this->infoLink = $infoLink;
        $this->canonicalVolumeLink = $canonicalVolumeLink;
        $this->subtitle = $subtitle;
        $this->averageRating = $averageRating;
        $this->ratingsCount = $ratingsCount;
        $this->seriesInfo = $seriesInfo;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return string[]|null
     */
    public function getAuthors(): ?array
    {
        return $this->authors;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function getPublishedDate(): ?string
    {
        return $this->publishedDate;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return IndustryIdentifiers[]|null
     */
    public function getIndustryIdentifiers(): ?array
    {
        return $this->industryIdentifiers;
    }

    public function getReadingModes(): ?ReadingModes
    {
        return $this->readingModes;
    }

    public function getPageCount(): ?int
    {
        return $this->pageCount;
    }

    public function getPrintType(): ?string
    {
        return $this->printType;
    }

    /**
     * @return string[]|null
     */
    public function getCategories(): ?array
    {
        return $this->categories;
    }

    public function getMaturityRating(): ?string
    {
        return $this->maturityRating;
    }

    public function getAllowAnonLogging(): ?bool
    {
        return $this->allowAnonLogging;
    }

    public function getContentVersion(): ?string
    {
        return $this->contentVersion;
    }

    public function getPanelizationSummary(): ?PanelizationSummary
    {
        return $this->panelizationSummary;
    }

    public function getImageLinks(): ?ImageLinks
    {
        return $this->imageLinks;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getPreviewLink(): ?string
    {
        return $this->previewLink;
    }

    public function getInfoLink(): ?string
    {
        return $this->infoLink;
    }

    public function getCanonicalVolumeLink(): ?string
    {
        return $this->canonicalVolumeLink;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function getAverageRating(): float|int|null
    {
        return $this->averageRating;
    }

    public function getRatingsCount(): ?int
    {
        return $this->ratingsCount;
    }

    public function getSeriesInfo(): ?SeriesInfo
    {
        return $this->seriesInfo;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['title'] ?? null,
            $data['authors'] ?? null,
            $data['publisher'] ?? null,
            $data['publishedDate'] ?? null,
            $data['description'] ?? null,
            ($data['industryIdentifiers'] ?? null) !== null ? array_map(static function ($data) {
                return IndustryIdentifiers::fromJson($data);
            }, $data['industryIdentifiers']) : null,
            ($data['readingModes'] ?? null) !== null ? ReadingModes::fromJson($data['readingModes']) : null,
            $data['pageCount'] ?? null,
            $data['printType'] ?? null,
            $data['categories'] ?? null,
            $data['maturityRating'] ?? null,
            $data['allowAnonLogging'] ?? null,
            $data['contentVersion'] ?? null,
            ($data['panelizationSummary'] ?? null) !== null ? PanelizationSummary::fromJson($data['panelizationSummary']) : null,
            ($data['imageLinks'] ?? null) !== null ? ImageLinks::fromJson($data['imageLinks']) : null,
            $data['language'] ?? null,
            $data['previewLink'] ?? null,
            $data['infoLink'] ?? null,
            $data['canonicalVolumeLink'] ?? null,
            $data['subtitle'] ?? null,
            $data['averageRating'] ?? null,
            $data['ratingsCount'] ?? null,
            ($data['seriesInfo'] ?? null) !== null ? SeriesInfo::fromJson($data['seriesInfo']) : null
        );
    }
}
