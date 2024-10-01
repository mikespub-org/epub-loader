<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class UserMap
{
    public ?int $id;
    public ?string $typename;
    public ?int $legacyId;
    public ?string $imageUrlSquare;
    public ?bool $isAuthor;
    public ?int $textReviewsCount;
    public null $viewerRelationshipStatus;
    public ?string $name;
    public ?string $webUrl;
    public ?Contributor $contributor;
    public ?int $followersCount;

    public function __construct(
        ?int $id,
        ?string $typename,
        ?int $legacyId,
        ?string $imageUrlSquare,
        ?bool $isAuthor,
        ?int $textReviewsCount,
        null $viewerRelationshipStatus,
        ?string $name,
        ?string $webUrl,
        ?Contributor $contributor,
        ?int $followersCount
    ) {
        $this->id = $id;
        $this->typename = $typename;
        $this->legacyId = $legacyId;
        $this->imageUrlSquare = $imageUrlSquare;
        $this->isAuthor = $isAuthor;
        $this->textReviewsCount = $textReviewsCount;
        $this->viewerRelationshipStatus = $viewerRelationshipStatus;
        $this->name = $name;
        $this->webUrl = $webUrl;
        $this->contributor = $contributor;
        $this->followersCount = $followersCount;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getLegacyId(): ?int
    {
        return $this->legacyId;
    }

    public function getImageUrlSquare(): ?string
    {
        return $this->imageUrlSquare;
    }

    public function getIsAuthor(): ?bool
    {
        return $this->isAuthor;
    }

    public function getTextReviewsCount(): ?int
    {
        return $this->textReviewsCount;
    }

    public function getViewerRelationshipStatus(): null
    {
        return $this->viewerRelationshipStatus;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getWebUrl(): ?string
    {
        return $this->webUrl;
    }

    public function getContributor(): ?Contributor
    {
        return $this->contributor;
    }

    public function getFollowersCount(): ?int
    {
        return $this->followersCount;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['__typename'] ?? null,
            $data['legacyId'] ?? null,
            $data['imageUrlSquare'] ?? null,
            $data['isAuthor'] ?? null,
            $data['textReviewsCount'] ?? null,
            $data['viewerRelationshipStatus'] ?? null,
            $data['name'] ?? null,
            $data['webUrl'] ?? null,
            ($data['contributor'] ?? null) !== null ? Contributor::fromJson($data['contributor']) : null,
            $data['followersCount'] ?? null
        );
    }
}
