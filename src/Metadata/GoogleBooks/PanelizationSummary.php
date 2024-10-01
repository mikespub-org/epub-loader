<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

class PanelizationSummary
{
    public ?bool $containsEpubBubbles;
    public ?bool $containsImageBubbles;

    public function __construct(?bool $containsEpubBubbles, ?bool $containsImageBubbles)
    {
        $this->containsEpubBubbles = $containsEpubBubbles;
        $this->containsImageBubbles = $containsImageBubbles;
    }

    public function getContainsEpubBubbles(): ?bool
    {
        return $this->containsEpubBubbles;
    }

    public function getContainsImageBubbles(): ?bool
    {
        return $this->containsImageBubbles;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['containsEpubBubbles'] ?? null,
            $data['containsImageBubbles'] ?? null
        );
    }
}
