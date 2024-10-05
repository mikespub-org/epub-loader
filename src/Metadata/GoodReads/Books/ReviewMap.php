<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class ReviewMap
{
    public ?string $id;
    public ?string $typename;
    public ?Creator $creator;
    public ?string $recommendFor;
    public ?int $updatedAt;
    public ?int $createdAt;
    public ?bool $spoilerStatus;
    public ?int $lastRevisionAt;
    public ?string $text;
    public ?int $rating;
    public ?Shelving $shelving;
    public ?int $likeCount;
    public mixed $viewerHasLiked;
    public ?int $commentCount;

    public function __construct(
        ?string $id,
        ?string $typename,
        ?Creator $creator,
        ?string $recommendFor,
        ?int $updatedAt,
        ?int $createdAt,
        ?bool $spoilerStatus,
        ?int $lastRevisionAt,
        ?string $text,
        ?int $rating,
        ?Shelving $shelving,
        ?int $likeCount,
        mixed $viewerHasLiked,
        ?int $commentCount
    ) {
        $this->id = $id;
        $this->typename = $typename;
        $this->creator = $creator;
        $this->recommendFor = $recommendFor;
        $this->updatedAt = $updatedAt;
        $this->createdAt = $createdAt;
        $this->spoilerStatus = $spoilerStatus;
        $this->lastRevisionAt = $lastRevisionAt;
        $this->text = $text;
        $this->rating = $rating;
        $this->shelving = $shelving;
        $this->likeCount = $likeCount;
        $this->viewerHasLiked = $viewerHasLiked;
        $this->commentCount = $commentCount;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getCreator(): ?Creator
    {
        return $this->creator;
    }

    public function getRecommendFor(): ?string
    {
        return $this->recommendFor;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function getSpoilerStatus(): ?bool
    {
        return $this->spoilerStatus;
    }

    public function getLastRevisionAt(): ?int
    {
        return $this->lastRevisionAt;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function getShelving(): ?Shelving
    {
        return $this->shelving;
    }

    public function getLikeCount(): ?int
    {
        return $this->likeCount;
    }

    public function getViewerHasLiked(): mixed
    {
        return $this->viewerHasLiked;
    }

    public function getCommentCount(): ?int
    {
        return $this->commentCount;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['__typename'] ?? null,
            ($data['creator'] ?? null) !== null ? Creator::fromJson($data['creator']) : null,
            $data['recommendFor'] ?? null,
            $data['updatedAt'] ?? null,
            $data['createdAt'] ?? null,
            $data['spoilerStatus'] ?? null,
            $data['lastRevisionAt'] ?? null,
            $data['text'] ?? null,
            $data['rating'] ?? null,
            ($data['shelving'] ?? null) !== null ? Shelving::fromJson($data['shelving']) : null,
            $data['likeCount'] ?? null,
            $data['viewerHasLiked'] ?? null,
            $data['commentCount'] ?? null
        );
    }
}
