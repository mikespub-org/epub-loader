<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class RootQuery
{
    public ?string $typename;
    public ?GetAdsTargeting $getAdsTargeting;
    public ?GetSiteHeaderBanner $getSiteHeaderBanner;
    public ?GetBookByLegacyId $getBookByLegacyId;
    public ?GetPageBanner $getPageBanner;
    public ?GetReviews $getReviews;

    public function __construct(
        ?string $typename,
        ?GetAdsTargeting $getAdsTargeting,
        ?GetSiteHeaderBanner $getSiteHeaderBanner,
        ?GetBookByLegacyId $getBookByLegacyId,
        ?GetPageBanner $getPageBanner,
        ?GetReviews $getReviews
    ) {
        $this->typename = $typename;
        $this->getAdsTargeting = $getAdsTargeting;
        $this->getSiteHeaderBanner = $getSiteHeaderBanner;
        $this->getBookByLegacyId = $getBookByLegacyId;
        $this->getPageBanner = $getPageBanner;
        $this->getReviews = $getReviews;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    /**
     * getAdsTargeting({\"getAdsTargetingInput\":{\"contextual\":{}}}) = one per book
     */
    public function getGetAdsTargeting(): ?GetAdsTargeting
    {
        return $this->getAdsTargeting;
    }

    public function getGetSiteHeaderBanner(): ?GetSiteHeaderBanner
    {
        return $this->getSiteHeaderBanner;
    }

    /**
     * getBookByLegacyId({\"legacyId\":\"2306655\"}) = one per book
     */
    public function getGetBookByLegacyId(): ?GetBookByLegacyId
    {
        return $this->getBookByLegacyId;
    }

    /**
     * getPageBanner({\"getPageBannerInput\":{\"id\":\"kca://book/amzn1.gr.book.v1.z5HWNE28-Z9FDLL4B0LXYA\",\"pageName\":\"book_show\"}}) = one per book
     */
    public function getGetPageBanner(): ?GetPageBanner
    {
        return $this->getPageBanner;
    }

    public function getGetReviews(): ?GetReviews
    {
        return $this->getReviews;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        // simulate patternProperties from JSON schema - single key here
        // getAdsTargeting({\"getAdsTargetingInput\":{\"contextual\":{}}}) = one per book
        $getAdsTargetingKeys = preg_grep('/^getAdsTargeting\(/', array_keys($data)) ?: [''];
        $getAdsTargetingKey = reset($getAdsTargetingKeys);
        // getBookByLegacyId({\"legacyId\":\"2306655\"}) = one per book
        $getBookByLegacyIdKeys = preg_grep('/^getBookByLegacyId\(/', array_keys($data)) ?: [''];
        $getBookByLegacyIdKey = reset($getBookByLegacyIdKeys);
        // getPageBanner({\"getPageBannerInput\":{\"id\":\"kca://book/amzn1.gr.book.v1.z5HWNE28-Z9FDLL4B0LXYA\",\"pageName\":\"book_show\"}}) = one per book
        $getPageBannerKeys = preg_grep('/^getPageBanner\(/', array_keys($data)) ?: [''];
        $getPageBannerKey = reset($getPageBannerKeys);
        return new self(
            $data['__typename'] ?? null,
            ($data[$getAdsTargetingKey] ?? null) !== null ? GetAdsTargeting::fromJson($data[$getAdsTargetingKey]) : null,
            ($data['getSiteHeaderBanner'] ?? null) !== null ? GetSiteHeaderBanner::fromJson($data['getSiteHeaderBanner']) : null,
            ($data[$getBookByLegacyIdKey] ?? null) !== null ? GetBookByLegacyId::fromJson($data[$getBookByLegacyIdKey]) : null,
            ($data[$getPageBannerKey] ?? null) !== null ? GetPageBanner::fromJson($data[$getPageBannerKey]) : null,
            ($data['getReviews'] ?? null) !== null ? GetReviews::fromJson($data['getReviews']) : null
        );
    }
}
