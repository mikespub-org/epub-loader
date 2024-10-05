<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Series;

class Description
{
    public ?string $truncatedHtml;
    public ?string $html;

    public function __construct(?string $truncatedHtml, ?string $html)
    {
        $this->truncatedHtml = $truncatedHtml;
        $this->html = $html;
    }

    public function getTruncatedHtml(): ?string
    {
        return $this->truncatedHtml;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['truncatedHtml'] ?? null,
            $data['html'] ?? null
        );
    }
}
