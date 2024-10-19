<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

class ReadingModes
{
    public ?bool $text;
    public ?bool $image;

    public function __construct(?bool $text, ?bool $image)
    {
        $this->text = $text;
        $this->image = $image;
    }

    public function getText(): ?bool
    {
        return $this->text;
    }

    public function getImage(): ?bool
    {
        return $this->image;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['text'] ?? null,
            $data['image'] ?? null
        );
    }
}
