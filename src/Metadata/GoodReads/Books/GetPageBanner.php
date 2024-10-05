<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

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
        return new self(
            $data['__typename'] ?? null,
            $data['type'] ?? null,
            $data['message'] ?? null
        );
    }
}
