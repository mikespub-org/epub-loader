<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            'finskyOfferType' => null,
            'listPrice' => OfferListPrice::fromJson(...),
            'retailPrice' => OfferRetailPrice::fromJson(...),
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
