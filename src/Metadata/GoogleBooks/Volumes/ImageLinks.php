<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            'smallThumbnail' => null,
            'thumbnail' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
