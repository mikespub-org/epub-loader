<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

class ListPrice
{
    public ?float $amount;
    public ?string $currencyCode;

    public function __construct(?float $amount, ?string $currencyCode)
    {
        $this->amount = $amount;
        $this->currencyCode = $currencyCode;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
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
            $data['amount'] ?? null,
            $data['currencyCode'] ?? null
        );
    }
}
