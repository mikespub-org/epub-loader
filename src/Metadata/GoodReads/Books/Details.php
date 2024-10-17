<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class Details
{
    public ?string $typename;
    public ?string $asin;
    public ?string $format;
    public ?int $numPages;
    public ?string $webUrl;
    public ?string $shelvesUrl;
    public ?int $publicationTime;
    public ?string $originalTitle;
    /** @var array<mixed>|null */
    public ?array $awardsWon;
    /** @var array<mixed>|null */
    public ?array $places;
    /** @var array<mixed>|null */
    public ?array $characters;
    public ?string $publisher;
    public ?string $isbn;
    public ?string $isbn13;
    public ?Language $language;

    /**
     * @param array<mixed>|null $awardsWon
     * @param array<mixed>|null $places
     * @param array<mixed>|null $characters
     */
    public function __construct(
        ?string $typename,
        ?string $asin,
        ?string $format,
        ?int $numPages,
        ?string $webUrl,
        ?string $shelvesUrl,
        ?int $publicationTime,
        ?string $originalTitle,
        ?array $awardsWon,
        ?array $places,
        ?array $characters,
        ?string $publisher,
        ?string $isbn,
        ?string $isbn13,
        ?Language $language
    ) {
        $this->typename = $typename;
        $this->asin = $asin;
        $this->format = $format;
        $this->numPages = $numPages;
        $this->webUrl = $webUrl;
        $this->shelvesUrl = $shelvesUrl;
        $this->publicationTime = $publicationTime;
        $this->originalTitle = $originalTitle;
        $this->awardsWon = $awardsWon;
        $this->places = $places;
        $this->characters = $characters;
        $this->publisher = $publisher;
        $this->isbn = $isbn;
        $this->isbn13 = $isbn13;
        $this->language = $language;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getAsin(): ?string
    {
        return $this->asin;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getNumPages(): ?int
    {
        return $this->numPages;
    }

    public function getWebUrl(): ?string
    {
        return $this->webUrl;
    }

    public function getShelvesUrl(): ?string
    {
        return $this->shelvesUrl;
    }

    public function getPublicationTime(): ?int
    {
        return $this->publicationTime;
    }

    public function getOriginalTitle(): ?string
    {
        return $this->originalTitle;
    }

    /**
     * @return array<mixed>|null
     */
    public function getAwardsWon(): ?array
    {
        return $this->awardsWon;
    }

    /**
     * @return array<mixed>|null
     */
    public function getPlaces(): ?array
    {
        return $this->places;
    }

    /**
     * @return array<mixed>|null
     */
    public function getCharacters(): ?array
    {
        return $this->characters;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function getIsbn13(): ?string
    {
        return $this->isbn13;
    }

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['asin'] ?? null,
            $data['format'] ?? null,
            $data['numPages'] ?? null,
            $data['webUrl'] ?? null,
            $data['shelvesUrl'] ?? null,
            $data['publicationTime'] ?? null,
            $data['originalTitle'] ?? null,
            $data['awardsWon'] ?? null,
            $data['places'] ?? null,
            $data['characters'] ?? null,
            $data['publisher'] ?? null,
            $data['isbn'] ?? null,
            $data['isbn13'] ?? null,
            ($data['language'] ?? null) !== null ? Language::fromJson($data['language']) : null
        );
    }
}
