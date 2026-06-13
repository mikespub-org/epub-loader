<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Series;

use Marsender\EPubLoader\Metadata\Mapper;

class Author
{
    public ?int $id;
    public ?string $name;
    public ?bool $isGoodreadsAuthor;
    public ?string $profileUrl;
    public ?string $worksListUrl;

    public function __construct(
        ?int $id,
        ?string $name,
        ?bool $isGoodreadsAuthor,
        ?string $profileUrl,
        ?string $worksListUrl
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->isGoodreadsAuthor = $isGoodreadsAuthor;
        $this->profileUrl = $profileUrl;
        $this->worksListUrl = $worksListUrl;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getIsGoodreadsAuthor(): ?bool
    {
        return $this->isGoodreadsAuthor;
    }

    public function getProfileUrl(): ?string
    {
        return $this->profileUrl;
    }

    public function getWorksListUrl(): ?string
    {
        return $this->worksListUrl;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            'id' => null,
            'name' => null,
            'isGoodreadsAuthor' => null,
            'profileUrl' => null,
            'worksListUrl' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
