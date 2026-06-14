<?php

/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 */

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Search;

use Marsender\EPubLoader\Metadata\Mapper;

class WorkSearchResult
{
    public ?int $numFound;
    public ?int $start;
    public ?bool $numFoundExact;
    /** @var WorkDocs[]|null */
    public ?array $docs;
    public ?int $num_found = null;
    public ?string $q;
    public mixed $offset;

    /**
     * @param WorkDocs[]|null $docs
     */
    public function __construct(
        ?int $numFound,
        ?int $start,
        ?bool $numFoundExact,
        ?array $docs,
        //?int $num_found,
        ?string $q,
        mixed $offset
    ) {
        $this->numFound = $numFound;
        $this->start = $start;
        $this->numFoundExact = $numFoundExact;
        $this->docs = $docs;
        //$this->num_found = $num_found;
        $this->q = $q;
        $this->offset = $offset;
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
     * @return WorkDocs[]|null
     */
    public function getDocs(): ?array
    {
        return $this->docs;
    }

    public function getNum_Found(): ?int
    {
        return $this->num_found;
    }

    public function getQ(): ?string
    {
        return $this->q;
    }

    public function getOffset(): mixed
    {
        return $this->offset;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            'numFound' => null,
            'start' => null,
            'numFoundExact' => null,
            'docs' => [ WorkDocs::fromJson(...) ],
            //'num_found' => null,
            'q' => null,
            'offset' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
