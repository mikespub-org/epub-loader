<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class FeatureFlags
{
    public ?string $typename;
    public ?bool $hideAds;
    public ?bool $noIndex;
    public ?bool $noReviews;
    public ?bool $noNewRatings;
    public ?bool $noNewTextReviews;

    public function __construct(
        ?string $typename,
        ?bool $hideAds,
        ?bool $noIndex,
        ?bool $noReviews,
        ?bool $noNewRatings,
        ?bool $noNewTextReviews
    ) {
        $this->typename = $typename;
        $this->hideAds = $hideAds;
        $this->noIndex = $noIndex;
        $this->noReviews = $noReviews;
        $this->noNewRatings = $noNewRatings;
        $this->noNewTextReviews = $noNewTextReviews;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getHideAds(): ?bool
    {
        return $this->hideAds;
    }

    public function getNoIndex(): ?bool
    {
        return $this->noIndex;
    }

    public function getNoReviews(): ?bool
    {
        return $this->noReviews;
    }

    public function getNoNewRatings(): ?bool
    {
        return $this->noNewRatings;
    }

    public function getNoNewTextReviews(): ?bool
    {
        return $this->noNewTextReviews;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['hideAds'] ?? null,
            $data['noIndex'] ?? null,
            $data['noReviews'] ?? null,
            $data['noNewRatings'] ?? null,
            $data['noNewTextReviews'] ?? null
        );
    }
}
