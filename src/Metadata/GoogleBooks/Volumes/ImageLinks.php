<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

class ImageLinks
{
    public ?string $smallThumbnail;
    public ?string $thumbnail;

    public function __construct(?string $smallThumbnail, ?string $thumbnail)
    {
        $this->smallThumbnail = $smallThumbnail;
        $this->thumbnail = $thumbnail;
    }

    public function getSmallThumbnail(): ?string
    {
        return $this->smallThumbnail;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['smallThumbnail'] ?? null,
            $data['thumbnail'] ?? null
        );
    }
}
