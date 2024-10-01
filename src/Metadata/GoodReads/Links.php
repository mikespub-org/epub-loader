<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class Links
{
    public ?string $typename;
    public ?PrimaryAffiliateLink $primaryAffiliateLink;
    /** @var SecondaryAffiliateLinks[]|null */
    public ?array $secondaryAffiliateLinks;
    /** @var LibraryLinks[]|null */
    public ?array $libraryLinks;
    public ?string $overflowPageUrl;
    public ?SeriesLink $seriesLink;

    /**
     * @param SecondaryAffiliateLinks[]|null $secondaryAffiliateLinks
     * @param LibraryLinks[]|null $libraryLinks
     */
    public function __construct(
        ?string $typename,
        ?PrimaryAffiliateLink $primaryAffiliateLink,
        ?array $secondaryAffiliateLinks,
        ?array $libraryLinks,
        ?string $overflowPageUrl,
        ?SeriesLink $seriesLink
    ) {
        $this->typename = $typename;
        $this->primaryAffiliateLink = $primaryAffiliateLink;
        $this->secondaryAffiliateLinks = $secondaryAffiliateLinks;
        $this->libraryLinks = $libraryLinks;
        $this->overflowPageUrl = $overflowPageUrl;
        $this->seriesLink = $seriesLink;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getPrimaryAffiliateLink(): ?PrimaryAffiliateLink
    {
        return $this->primaryAffiliateLink;
    }

    /**
     * @return SecondaryAffiliateLinks[]|null
     */
    public function getSecondaryAffiliateLinks(): ?array
    {
        return $this->secondaryAffiliateLinks;
    }

    /**
     * @return LibraryLinks[]|null
     */
    public function getLibraryLinks(): ?array
    {
        return $this->libraryLinks;
    }

    public function getOverflowPageUrl(): ?string
    {
        return $this->overflowPageUrl;
    }

    public function getSeriesLink(): ?SeriesLink
    {
        return $this->seriesLink;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            ($data['primaryAffiliateLink'] ?? null) !== null ? PrimaryAffiliateLink::fromJson($data['primaryAffiliateLink']) : null,
            ($data['secondaryAffiliateLinks'] ?? null) !== null ? array_map(static function ($data) {
                return SecondaryAffiliateLinks::fromJson($data);
            }, $data['secondaryAffiliateLinks']) : null,
            ($data['libraryLinks'] ?? null) !== null ? array_map(static function ($data) {
                return LibraryLinks::fromJson($data);
            }, $data['libraryLinks']) : null,
            $data['overflowPageUrl'] ?? null,
            ($data['seriesLink'] ?? null) !== null ? SeriesLink::fromJson($data['seriesLink']) : null
        );
    }
}
