<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class PrimaryAffiliateLink
{
    public ?string $typename;
    public ?string $name;
    public ?string $url;
    public ?string $ref;
    public ?string $ebookPrice;
    public ?bool $kuEligible;
    public ?bool $primeEligible;

    public function __construct(
        ?string $typename,
        ?string $name,
        ?string $url,
        ?string $ref,
        ?string $ebookPrice,
        ?bool $kuEligible,
        ?bool $primeEligible
    ) {
        $this->typename = $typename;
        $this->name = $name;
        $this->url = $url;
        $this->ref = $ref;
        $this->ebookPrice = $ebookPrice;
        $this->kuEligible = $kuEligible;
        $this->primeEligible = $primeEligible;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function getEbookPrice(): ?string
    {
        return $this->ebookPrice;
    }

    public function getKuEligible(): ?bool
    {
        return $this->kuEligible;
    }

    public function getPrimeEligible(): ?bool
    {
        return $this->primeEligible;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['name'] ?? null,
            $data['url'] ?? null,
            $data['ref'] ?? null,
            $data['ebookPrice'] ?? null,
            $data['kuEligible'] ?? null,
            $data['primeEligible'] ?? null
        );
    }
}
