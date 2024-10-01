<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class AwardsWon
{
    public ?string $typename;
    public ?string $name;
    public ?string $webUrl;
    public ?int $awardedAt;
    public ?string $category;
    public ?string $designation;

    public function __construct(
        ?string $typename,
        ?string $name,
        ?string $webUrl,
        ?int $awardedAt,
        ?string $category,
        ?string $designation
    ) {
        $this->typename = $typename;
        $this->name = $name;
        $this->webUrl = $webUrl;
        $this->awardedAt = $awardedAt;
        $this->category = $category;
        $this->designation = $designation;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getWebUrl(): ?string
    {
        return $this->webUrl;
    }

    public function getAwardedAt(): ?int
    {
        return $this->awardedAt;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['name'] ?? null,
            $data['webUrl'] ?? null,
            $data['awardedAt'] ?? null,
            $data['category'] ?? null,
            $data['designation'] ?? null
        );
    }
}
