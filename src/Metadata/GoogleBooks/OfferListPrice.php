<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

class OfferListPrice
{
    public ?int $amountInMicros;
    public ?string $currencyCode;

    public function __construct(?int $amountInMicros, ?string $currencyCode)
    {
        $this->amountInMicros = $amountInMicros;
        $this->currencyCode = $currencyCode;
    }

    public function getAmountInMicros(): ?int
    {
        return $this->amountInMicros;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['amountInMicros'] ?? null,
            $data['currencyCode'] ?? null
        );
    }
}
