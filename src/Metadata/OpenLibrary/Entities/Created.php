<?php

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Entities;

use Marsender\EPubLoader\Metadata\Mapper;

class Created
{
    public ?string $type;
    public ?string $value;

    public function __construct(?string $type, ?string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            'type' => null,
            'value' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
