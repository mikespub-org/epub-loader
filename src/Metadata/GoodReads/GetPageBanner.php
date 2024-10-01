<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class GetPageBanner
{
    public ?string $typename;
    public null $type;
    public null $message;

    public function __construct(
        ?string $typename,
        null $type,
        null $message
    ) {
        $this->typename = $typename;
        $this->type = $type;
        $this->message = $message;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getType(): null
    {
        return $this->type;
    }

    public function getMessage(): null
    {
        return $this->message;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['type'] ?? null,
            $data['message'] ?? null
        );
    }
}
