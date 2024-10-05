<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class Works
{
    public ?string $typename;
    public ?int $totalCount;

    public function __construct(?string $typename, ?int $totalCount)
    {
        $this->typename = $typename;
        $this->totalCount = $totalCount;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['totalCount'] ?? null
        );
    }
}
