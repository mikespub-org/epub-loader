<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class TextReviewsLanguageCounts
{
    public ?string $typename;
    public ?int $count;
    public ?string $isoLanguageCode;

    public function __construct(
        ?string $typename,
        ?int $count,
        ?string $isoLanguageCode
    ) {
        $this->typename = $typename;
        $this->count = $count;
        $this->isoLanguageCode = $isoLanguageCode;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function getIsoLanguageCode(): ?string
    {
        return $this->isoLanguageCode;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['count'] ?? null,
            $data['isoLanguageCode'] ?? null
        );
    }
}
