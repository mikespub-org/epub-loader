<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class GetAdsTargeting
{
    public ?string $typename;
    public null $behavioral;
    public null $contextual;

    public function __construct(
        ?string $typename,
        null $behavioral,
        null $contextual
    ) {
        $this->typename = $typename;
        $this->behavioral = $behavioral;
        $this->contextual = $contextual;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getBehavioral(): null
    {
        return $this->behavioral;
    }

    public function getContextual(): null
    {
        return $this->contextual;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['behavioral'] ?? null,
            $data['contextual'] ?? null
        );
    }
}
