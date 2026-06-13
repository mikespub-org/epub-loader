<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            'country' => null,
            'saleability' => null,
            'isEbook' => null,
            'listPrice' => ListPrice::fromJson(...),
            'retailPrice' => RetailPrice::fromJson(...),
            'buyLink' => null,
            'offers' => [ Offers::fromJson(...) ],
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
