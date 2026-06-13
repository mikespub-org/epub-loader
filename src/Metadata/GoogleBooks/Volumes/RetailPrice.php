<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

use Marsender\EPubLoader\Metadata\Mapper;

class RetailPrice
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
        $keys = [
            'amount' => null,
            'currencyCode' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
