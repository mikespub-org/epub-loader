<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            'textSnippet' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
