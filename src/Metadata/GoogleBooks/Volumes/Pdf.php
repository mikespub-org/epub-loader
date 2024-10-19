<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

class Pdf
{
    public ?bool $isAvailable;
    public ?string $acsTokenLink;

    public function __construct(?bool $isAvailable, ?string $acsTokenLink)
    {
        $this->isAvailable = $isAvailable;
        $this->acsTokenLink = $acsTokenLink;
    }

    public function getIsAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function getAcsTokenLink(): ?string
    {
        return $this->acsTokenLink;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['isAvailable'] ?? null,
            $data['acsTokenLink'] ?? null
        );
    }
}
