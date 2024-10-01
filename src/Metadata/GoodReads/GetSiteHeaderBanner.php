<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class GetSiteHeaderBanner
{
    public ?string $typename;
    public ?string $altText;
    public ?string $clickthroughUrl;
    public ?string $desktop1xPhoto;
    public ?string $desktop2xPhoto;
    public ?string $mobile1xPhoto;
    public ?string $mobile2xPhoto;
    public ?string $siteStripColor;

    public function __construct(
        ?string $typename,
        ?string $altText,
        ?string $clickthroughUrl,
        ?string $desktop1xPhoto,
        ?string $desktop2xPhoto,
        ?string $mobile1xPhoto,
        ?string $mobile2xPhoto,
        ?string $siteStripColor
    ) {
        $this->typename = $typename;
        $this->altText = $altText;
        $this->clickthroughUrl = $clickthroughUrl;
        $this->desktop1xPhoto = $desktop1xPhoto;
        $this->desktop2xPhoto = $desktop2xPhoto;
        $this->mobile1xPhoto = $mobile1xPhoto;
        $this->mobile2xPhoto = $mobile2xPhoto;
        $this->siteStripColor = $siteStripColor;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function getClickthroughUrl(): ?string
    {
        return $this->clickthroughUrl;
    }

    public function getDesktop1xPhoto(): ?string
    {
        return $this->desktop1xPhoto;
    }

    public function getDesktop2xPhoto(): ?string
    {
        return $this->desktop2xPhoto;
    }

    public function getMobile1xPhoto(): ?string
    {
        return $this->mobile1xPhoto;
    }

    public function getMobile2xPhoto(): ?string
    {
        return $this->mobile2xPhoto;
    }

    public function getSiteStripColor(): ?string
    {
        return $this->siteStripColor;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['altText'] ?? null,
            $data['clickthroughUrl'] ?? null,
            $data['desktop1xPhoto'] ?? null,
            $data['desktop2xPhoto'] ?? null,
            $data['mobile1xPhoto'] ?? null,
            $data['mobile2xPhoto'] ?? null,
            $data['siteStripColor'] ?? null
        );
    }
}
