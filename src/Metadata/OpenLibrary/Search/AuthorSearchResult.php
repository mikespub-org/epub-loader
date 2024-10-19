<?php
/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 */

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Search;

class AuthorSearchResult
{
    public ?int $numFound;
    public ?int $start;
    public ?bool $numFoundExact;
    /** @var AuthorDocs[]|null */
    public ?array $docs;

    /**
     * @param AuthorDocs[]|null $docs
     */
    public function __construct(
        ?int $numFound,
        ?int $start,
        ?bool $numFoundExact,
        ?array $docs
    ) {
        $this->numFound = $numFound;
        $this->start = $start;
        $this->numFoundExact = $numFoundExact;
        $this->docs = $docs;
    }

    public function getNumFound(): ?int
    {
        return $this->numFound;
    }

    public function getStart(): ?int
    {
        return $this->start;
    }

    public function getNumFoundExact(): ?bool
    {
        return $this->numFoundExact;
    }

    /**
     * @return AuthorDocs[]|null
     */
    public function getDocs(): ?array
    {
        return $this->docs;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['numFound'] ?? null,
            $data['start'] ?? null,
            $data['numFoundExact'] ?? null,
            ($data['docs'] ?? null) !== null ? array_map(static function ($data) {
                return AuthorDocs::fromJson($data);
            }, $data['docs']) : null
        );
    }
}
