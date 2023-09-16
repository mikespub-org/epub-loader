<?php
/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 */

namespace Marsender\EPubLoader\GoogleBooks;

class SearchResult
{
    public ?string $kind;
    public ?int $totalItems;
    /** @var Volume[]|null */
    public ?array $items;

    /**
     * @param Volume[]|null $items
     */
    public function __construct(
        ?string $kind,
        ?int $totalItems,
        ?array $items
    ) {
        $this->kind = $kind;
        $this->totalItems = $totalItems;
        $this->items = $items;
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function getTotalItems(): ?int
    {
        return $this->totalItems;
    }

    /**
     * @return Volume[]|null
     */
    public function getItems(): ?array
    {
        return $this->items;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['kind'] ?? null,
            $data['totalItems'] ?? null,
            ($data['items'] ?? null) !== null ? array_map(static function ($data) {
                return Volume::fromJson($data);
            }, $data['items']) : null
        );
    }
}

class Volume
{
    public ?string $kind;
    public ?string $id;
    public ?string $etag;
    public ?string $selfLink;
    public ?VolumeInfo $volumeInfo;
    public ?SaleInfo $saleInfo;
    public ?AccessInfo $accessInfo;
    public ?SearchInfo $searchInfo;

    public function __construct(
        ?string $kind,
        ?string $id,
        ?string $etag,
        ?string $selfLink,
        ?VolumeInfo $volumeInfo,
        ?SaleInfo $saleInfo,
        ?AccessInfo $accessInfo,
        ?SearchInfo $searchInfo
    ) {
        $this->kind = $kind;
        $this->id = $id;
        $this->etag = $etag;
        $this->selfLink = $selfLink;
        $this->volumeInfo = $volumeInfo;
        $this->saleInfo = $saleInfo;
        $this->accessInfo = $accessInfo;
        $this->searchInfo = $searchInfo;
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEtag(): ?string
    {
        return $this->etag;
    }

    public function getSelfLink(): ?string
    {
        return $this->selfLink;
    }

    public function getVolumeInfo(): ?VolumeInfo
    {
        return $this->volumeInfo;
    }

    public function getSaleInfo(): ?SaleInfo
    {
        return $this->saleInfo;
    }

    public function getAccessInfo(): ?AccessInfo
    {
        return $this->accessInfo;
    }

    public function getSearchInfo(): ?SearchInfo
    {
        return $this->searchInfo;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['kind'] ?? null,
            $data['id'] ?? null,
            $data['etag'] ?? null,
            $data['selfLink'] ?? null,
            ($data['volumeInfo'] ?? null) !== null ? VolumeInfo::fromJson($data['volumeInfo']) : null,
            ($data['saleInfo'] ?? null) !== null ? SaleInfo::fromJson($data['saleInfo']) : null,
            ($data['accessInfo'] ?? null) !== null ? AccessInfo::fromJson($data['accessInfo']) : null,
            ($data['searchInfo'] ?? null) !== null ? SearchInfo::fromJson($data['searchInfo']) : null
        );
    }
}

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

class IndustryIdentifiers
{
    public ?string $type;
    public ?string $identifier;

    public function __construct(?string $type, ?string $identifier)
    {
        $this->type = $type;
        $this->identifier = $identifier;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['type'] ?? null,
            $data['identifier'] ?? null
        );
    }
}

class ReadingModes
{
    public ?bool $text;
    public ?bool $image;

    public function __construct(?bool $text, ?bool $image)
    {
        $this->text = $text;
        $this->image = $image;
    }

    public function getText(): ?bool
    {
        return $this->text;
    }

    public function getImage(): ?bool
    {
        return $this->image;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['text'] ?? null,
            $data['image'] ?? null
        );
    }
}

class PanelizationSummary
{
    public ?bool $containsEpubBubbles;
    public ?bool $containsImageBubbles;

    public function __construct(?bool $containsEpubBubbles, ?bool $containsImageBubbles)
    {
        $this->containsEpubBubbles = $containsEpubBubbles;
        $this->containsImageBubbles = $containsImageBubbles;
    }

    public function getContainsEpubBubbles(): ?bool
    {
        return $this->containsEpubBubbles;
    }

    public function getContainsImageBubbles(): ?bool
    {
        return $this->containsImageBubbles;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['containsEpubBubbles'] ?? null,
            $data['containsImageBubbles'] ?? null
        );
    }
}

class ImageLinks
{
    public ?string $smallThumbnail;
    public ?string $thumbnail;

    public function __construct(?string $smallThumbnail, ?string $thumbnail)
    {
        $this->smallThumbnail = $smallThumbnail;
        $this->thumbnail = $thumbnail;
    }

    public function getSmallThumbnail(): ?string
    {
        return $this->smallThumbnail;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['smallThumbnail'] ?? null,
            $data['thumbnail'] ?? null
        );
    }
}

class SeriesInfo
{
    public ?string $kind;
    public ?string $shortSeriesBookTitle;
    public ?string $bookDisplayNumber;
    /** @var VolumeSeries[]|null */
    public ?array $volumeSeries;

    /**
     * @param VolumeSeries[]|null $volumeSeries
     */
    public function __construct(
        ?string $kind,
        ?string $shortSeriesBookTitle,
        ?string $bookDisplayNumber,
        ?array $volumeSeries
    ) {
        $this->kind = $kind;
        $this->shortSeriesBookTitle = $shortSeriesBookTitle;
        $this->bookDisplayNumber = $bookDisplayNumber;
        $this->volumeSeries = $volumeSeries;
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function getShortSeriesBookTitle(): ?string
    {
        return $this->shortSeriesBookTitle;
    }

    public function getBookDisplayNumber(): ?string
    {
        return $this->bookDisplayNumber;
    }

    /**
     * @return VolumeSeries[]|null
     */
    public function getVolumeSeries(): ?array
    {
        return $this->volumeSeries;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['kind'] ?? null,
            $data['shortSeriesBookTitle'] ?? null,
            $data['bookDisplayNumber'] ?? null,
            ($data['volumeSeries'] ?? null) !== null ? array_map(static function ($data) {
                return VolumeSeries::fromJson($data);
            }, $data['volumeSeries']) : null
        );
    }
}

class VolumeSeries
{
    public ?string $seriesId;
    public ?string $seriesBookType;
    public ?int $orderNumber;

    public function __construct(
        ?string $seriesId,
        ?string $seriesBookType,
        ?int $orderNumber
    ) {
        $this->seriesId = $seriesId;
        $this->seriesBookType = $seriesBookType;
        $this->orderNumber = $orderNumber;
    }

    public function getSeriesId(): ?string
    {
        return $this->seriesId;
    }

    public function getSeriesBookType(): ?string
    {
        return $this->seriesBookType;
    }

    public function getOrderNumber(): ?int
    {
        return $this->orderNumber;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['seriesId'] ?? null,
            $data['seriesBookType'] ?? null,
            $data['orderNumber'] ?? null
        );
    }
}

class SaleInfo
{
    public ?string $country;
    public ?string $saleability;
    public ?bool $isEbook;
    public ?ListPrice $listPrice;
    public ?RetailPrice $retailPrice;
    public ?string $buyLink;
    /** @var Offers[]|null */
    public ?array $offers;

    /**
     * @param Offers[]|null $offers
     */
    public function __construct(
        ?string $country,
        ?string $saleability,
        ?bool $isEbook,
        ?ListPrice $listPrice,
        ?RetailPrice $retailPrice,
        ?string $buyLink,
        ?array $offers
    ) {
        $this->country = $country;
        $this->saleability = $saleability;
        $this->isEbook = $isEbook;
        $this->listPrice = $listPrice;
        $this->retailPrice = $retailPrice;
        $this->buyLink = $buyLink;
        $this->offers = $offers;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getSaleability(): ?string
    {
        return $this->saleability;
    }

    public function getIsEbook(): ?bool
    {
        return $this->isEbook;
    }

    public function getListPrice(): ?ListPrice
    {
        return $this->listPrice;
    }

    public function getRetailPrice(): ?RetailPrice
    {
        return $this->retailPrice;
    }

    public function getBuyLink(): ?string
    {
        return $this->buyLink;
    }

    /**
     * @return Offers[]|null
     */
    public function getOffers(): ?array
    {
        return $this->offers;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['country'] ?? null,
            $data['saleability'] ?? null,
            $data['isEbook'] ?? null,
            ($data['listPrice'] ?? null) !== null ? ListPrice::fromJson($data['listPrice']) : null,
            ($data['retailPrice'] ?? null) !== null ? RetailPrice::fromJson($data['retailPrice']) : null,
            $data['buyLink'] ?? null,
            ($data['offers'] ?? null) !== null ? array_map(static function ($data) {
                return Offers::fromJson($data);
            }, $data['offers']) : null
        );
    }
}

class ListPrice
{
    public ?float $amount;
    public ?string $currencyCode;

    public function __construct(?float $amount, ?string $currencyCode)
    {
        $this->amount = $amount;
        $this->currencyCode = $currencyCode;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['amount'] ?? null,
            $data['currencyCode'] ?? null
        );
    }
}

class RetailPrice
{
    public ?float $amount;
    public ?string $currencyCode;

    public function __construct(?float $amount, ?string $currencyCode)
    {
        $this->amount = $amount;
        $this->currencyCode = $currencyCode;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['amount'] ?? null,
            $data['currencyCode'] ?? null
        );
    }
}

class Offers
{
    public ?int $finskyOfferType;
    public ?OfferListPrice $listPrice;
    public ?OfferRetailPrice $retailPrice;

    public function __construct(
        ?int $finskyOfferType,
        ?OfferListPrice $listPrice,
        ?OfferRetailPrice $retailPrice
    ) {
        $this->finskyOfferType = $finskyOfferType;
        $this->listPrice = $listPrice;
        $this->retailPrice = $retailPrice;
    }

    public function getFinskyOfferType(): ?int
    {
        return $this->finskyOfferType;
    }

    public function getListPrice(): ?OfferListPrice
    {
        return $this->listPrice;
    }

    public function getRetailPrice(): ?OfferRetailPrice
    {
        return $this->retailPrice;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['finskyOfferType'] ?? null,
            ($data['listPrice'] ?? null) !== null ? OfferListPrice::fromJson($data['listPrice']) : null,
            ($data['retailPrice'] ?? null) !== null ? OfferRetailPrice::fromJson($data['retailPrice']) : null
        );
    }
}

class OfferListPrice
{
    public ?int $amountInMicros;
    public ?string $currencyCode;

    public function __construct(?int $amountInMicros, ?string $currencyCode)
    {
        $this->amountInMicros = $amountInMicros;
        $this->currencyCode = $currencyCode;
    }

    public function getAmountInMicros(): ?int
    {
        return $this->amountInMicros;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['amountInMicros'] ?? null,
            $data['currencyCode'] ?? null
        );
    }
}

class OfferRetailPrice
{
    public ?int $amountInMicros;
    public ?string $currencyCode;

    public function __construct(?int $amountInMicros, ?string $currencyCode)
    {
        $this->amountInMicros = $amountInMicros;
        $this->currencyCode = $currencyCode;
    }

    public function getAmountInMicros(): ?int
    {
        return $this->amountInMicros;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['amountInMicros'] ?? null,
            $data['currencyCode'] ?? null
        );
    }
}

class AccessInfo
{
    public ?string $country;
    public ?string $viewability;
    public ?bool $embeddable;
    public ?bool $publicDomain;
    public ?string $textToSpeechPermission;
    public ?Epub $epub;
    public ?Pdf $pdf;
    public ?string $webReaderLink;
    public ?string $accessViewStatus;
    public ?bool $quoteSharingAllowed;

    public function __construct(
        ?string $country,
        ?string $viewability,
        ?bool $embeddable,
        ?bool $publicDomain,
        ?string $textToSpeechPermission,
        ?Epub $epub,
        ?Pdf $pdf,
        ?string $webReaderLink,
        ?string $accessViewStatus,
        ?bool $quoteSharingAllowed
    ) {
        $this->country = $country;
        $this->viewability = $viewability;
        $this->embeddable = $embeddable;
        $this->publicDomain = $publicDomain;
        $this->textToSpeechPermission = $textToSpeechPermission;
        $this->epub = $epub;
        $this->pdf = $pdf;
        $this->webReaderLink = $webReaderLink;
        $this->accessViewStatus = $accessViewStatus;
        $this->quoteSharingAllowed = $quoteSharingAllowed;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getViewability(): ?string
    {
        return $this->viewability;
    }

    public function getEmbeddable(): ?bool
    {
        return $this->embeddable;
    }

    public function getPublicDomain(): ?bool
    {
        return $this->publicDomain;
    }

    public function getTextToSpeechPermission(): ?string
    {
        return $this->textToSpeechPermission;
    }

    public function getEpub(): ?Epub
    {
        return $this->epub;
    }

    public function getPdf(): ?Pdf
    {
        return $this->pdf;
    }

    public function getWebReaderLink(): ?string
    {
        return $this->webReaderLink;
    }

    public function getAccessViewStatus(): ?string
    {
        return $this->accessViewStatus;
    }

    public function getQuoteSharingAllowed(): ?bool
    {
        return $this->quoteSharingAllowed;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['country'] ?? null,
            $data['viewability'] ?? null,
            $data['embeddable'] ?? null,
            $data['publicDomain'] ?? null,
            $data['textToSpeechPermission'] ?? null,
            ($data['epub'] ?? null) !== null ? Epub::fromJson($data['epub']) : null,
            ($data['pdf'] ?? null) !== null ? Pdf::fromJson($data['pdf']) : null,
            $data['webReaderLink'] ?? null,
            $data['accessViewStatus'] ?? null,
            $data['quoteSharingAllowed'] ?? null
        );
    }
}

class Epub
{
    public ?bool $isAvailable;
    public ?string $acsTokenLink;

    public function __construct(?bool $isAvailable, ?string $acsTokenLink)
    {
        $this->isAvailable = $isAvailable;
        $this->acsTokenLink = $acsTokenLink;
    }

    public function getIsAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function getAcsTokenLink(): ?string
    {
        return $this->acsTokenLink;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['isAvailable'] ?? null,
            $data['acsTokenLink'] ?? null
        );
    }
}

class Pdf
{
    public ?bool $isAvailable;
    public ?string $acsTokenLink;

    public function __construct(?bool $isAvailable, ?string $acsTokenLink)
    {
        $this->isAvailable = $isAvailable;
        $this->acsTokenLink = $acsTokenLink;
    }

    public function getIsAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function getAcsTokenLink(): ?string
    {
        return $this->acsTokenLink;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['isAvailable'] ?? null,
            $data['acsTokenLink'] ?? null
        );
    }
}

class SearchInfo
{
    public ?string $textSnippet;

    public function __construct(?string $textSnippet)
    {
        $this->textSnippet = $textSnippet;
    }

    public function getTextSnippet(): ?string
    {
        return $this->textSnippet;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['textSnippet'] ?? null
        );
    }
}
