<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class GetAdsTargeting
{
    public ?string $typename;
    public mixed $behavioral;
    public mixed $contextual;

    public function __construct(
        ?string $typename,
        mixed $behavioral,
        mixed $contextual
    ) {
        $this->typename = $typename;
        $this->behavioral = $behavioral;
        $this->contextual = $contextual;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getBehavioral(): mixed
    {
        return $this->behavioral;
    }

    public function getContextual(): mixed
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
