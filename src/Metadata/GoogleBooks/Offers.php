<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

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
