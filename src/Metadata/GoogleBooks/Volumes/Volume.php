<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

use Marsender\EPubLoader\Metadata\Mapper;

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
            Mapper::getItem($data, 'volumeInfo', VolumeInfo::fromJson(...)),
            Mapper::getItem($data, 'saleInfo', SaleInfo::fromJson(...)),
            Mapper::getItem($data, 'accessInfo', AccessInfo::fromJson(...)),
            Mapper::getItem($data, 'searchInfo', SearchInfo::fromJson(...))
        );
    }
}
