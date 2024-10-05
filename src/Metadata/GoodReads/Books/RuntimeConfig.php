<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class RuntimeConfig
{
    public ?string $env;

    public function __construct(?string $env)
    {
        $this->env = $env;
    }

    public function getEnv(): ?string
    {
        return $this->env;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['env'] ?? null
        );
    }
}
