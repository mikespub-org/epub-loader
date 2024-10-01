<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class ContributorMap
{
    public ?string $id;
    public ?string $typename;
    public ?int $legacyId;
    public ?string $name;
    public ?string $description;
    public ?bool $isGrAuthor;
    public ?Works $works;
    public ?string $profileImageUrl;
    public ?string $webUrl;
    public mixed $user;
    public mixed $viewerIsFollowing;
    public ?Followers $followers;

    public function __construct(
        ?string $id,
        ?string $typename,
        ?int $legacyId,
        ?string $name,
        ?string $description,
        ?bool $isGrAuthor,
        ?Works $works,
        ?string $profileImageUrl,
        ?string $webUrl,
        mixed $user,
        mixed $viewerIsFollowing,
        ?Followers $followers
    ) {
        $this->id = $id;
        $this->typename = $typename;
        $this->legacyId = $legacyId;
        $this->name = $name;
        $this->description = $description;
        $this->isGrAuthor = $isGrAuthor;
        $this->works = $works;
        $this->profileImageUrl = $profileImageUrl;
        $this->webUrl = $webUrl;
        $this->user = $user;
        $this->viewerIsFollowing = $viewerIsFollowing;
        $this->followers = $followers;
    }

    public function getId(): ?string
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIsGrAuthor(): ?bool
    {
        return $this->isGrAuthor;
    }

    public function getWorks(): ?Works
    {
        return $this->works;
    }

    public function getProfileImageUrl(): ?string
    {
        return $this->profileImageUrl;
    }

    public function getWebUrl(): ?string
    {
        return $this->webUrl;
    }

    public function getUser(): mixed
    {
        return $this->user;
    }

    public function getViewerIsFollowing(): mixed
    {
        return $this->viewerIsFollowing;
    }

    public function getFollowers(): ?Followers
    {
        return $this->followers;
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
            $data['name'] ?? null,
            $data['description'] ?? null,
            $data['isGrAuthor'] ?? null,
            ($data['works'] ?? null) !== null ? Works::fromJson($data['works']) : null,
            $data['profileImageUrl'] ?? null,
            $data['webUrl'] ?? null,
            $data['user'] ?? null,
            $data['viewerIsFollowing'] ?? null,
            ($data['followers'] ?? null) !== null ? Followers::fromJson($data['followers']) : null
        );
    }
}
