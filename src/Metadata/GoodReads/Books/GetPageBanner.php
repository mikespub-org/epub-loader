<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

class GetPageBanner
{
    public ?string $typename;
    public mixed $type;
    public mixed $message;

    public function __construct(
        ?string $typename,
        mixed $type,
        mixed $message
    ) {
        $this->typename = $typename;
        $this->type = $type;
        $this->message = $message;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getType(): mixed
    {
        return $this->type;
    }

    public function getMessage(): mixed
    {
        return $this->message;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            '__typename' => null,
            'type' => null,
            'message' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
