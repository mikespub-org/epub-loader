<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

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
            Mapper::getItem($data, 'primaryAffiliateLink', PrimaryAffiliateLink::fromJson(...)),
            Mapper::getArray($data, 'secondaryAffiliateLinks', SecondaryAffiliateLinks::fromJson(...)),
            Mapper::getArray($data, 'libraryLinks', LibraryLinks::fromJson(...)),
            $data['overflowPageUrl'] ?? null,
            Mapper::getItem($data, 'seriesLink', SeriesLink::fromJson(...))
        );
    }
}
