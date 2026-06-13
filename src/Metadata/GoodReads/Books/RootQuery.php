<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

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
        /**
        $keys = [
            '__typename'             => null,
            // getAdsTargeting({\"getAdsTargetingInput\":{\"contextual\":{}}}) = one per book
            '/^getAdsTargeting\(/'   => GetAdsTargeting::fromJson(...),
            'getSiteHeaderBanner'    => GetSiteHeaderBanner::fromJson(...),
            // getBookByLegacyId({\"legacyId\":\"2306655\"}) = one per book
            '/^getBookByLegacyId\(/' => GetBookByLegacyId::fromJson(...),
            // getPageBanner({\"getPageBannerInput\":{\"id\":\"kca://book/amzn1.gr.book.v1.z5HWNE28-Z9FDLL4B0LXYA\",\"pageName\":\"book_show\"}}) = one per book
            '/^getPageBanner\(/'     => GetPageBanner::fromJson(...),
            'getReviews'             => GetReviews::fromJson(...),
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
         */
        return new self(
            typename: $data['__typename'] ?? null,
            // getAdsTargeting({\"getAdsTargetingInput\":{\"contextual\":{}}}) = one per book
            getAdsTargeting: Mapper::getPatternItem($data, '/^getAdsTargeting\(/', GetAdsTargeting::fromJson(...)),
            getSiteHeaderBanner: Mapper::getItem($data, 'getSiteHeaderBanner', GetSiteHeaderBanner::fromJson(...)),
            // getBookByLegacyId({\"legacyId\":\"2306655\"}) = one per book
            getBookByLegacyId: Mapper::getPatternItem($data, '/^getBookByLegacyId\(/', GetBookByLegacyId::fromJson(...)),
            // getPageBanner({\"getPageBannerInput\":{\"id\":\"kca://book/amzn1.gr.book.v1.z5HWNE28-Z9FDLL4B0LXYA\",\"pageName\":\"book_show\"}}) = one per book
            getPageBanner: Mapper::getPatternItem($data, '/^getPageBanner\(/', GetPageBanner::fromJson(...)),
            getReviews: Mapper::getItem($data, 'getReviews', GetReviews::fromJson(...)),
        );
    }
}
