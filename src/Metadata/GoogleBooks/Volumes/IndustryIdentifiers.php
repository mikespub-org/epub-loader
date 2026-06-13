<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

use Marsender\EPubLoader\Metadata\Mapper;

class IndustryIdentifiers
{
    public ?string $type;
    public ?string $identifier;

    public function __construct(?string $type, ?string $identifier)
    {
        $this->type = $type;
        $this->identifier = $identifier;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            'type' => null,
            'identifier' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
