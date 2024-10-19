<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

class SearchInfo
{
    public ?string $textSnippet;

    public function __construct(?string $textSnippet)
    {
        $this->textSnippet = $textSnippet;
    }

    public function getTextSnippet(): ?string
    {
        return $this->textSnippet;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['textSnippet'] ?? null
        );
    }
}
