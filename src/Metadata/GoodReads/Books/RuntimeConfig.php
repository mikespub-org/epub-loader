<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            'env' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
