<?php
/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 * adapted for patternProperties in SearchResult - see JSON schema
 */

namespace Marsender\EPubLoader\Metadata\GoodReads;

class SearchResult
{
    /** @var array<string, AuthorMap>|null */
    public ?array $resultMap;

    /**
     * @param array<string, AuthorMap>|null $resultMap
     */
    public function __construct(?array $resultMap)
    {
        $this->resultMap = $resultMap;
    }

    /**
     * @return array<string, AuthorMap>|null
     */
    public function getResultMap(): ?array
    {
        return $this->resultMap;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        // simulate patternProperties from JSON schema - multiple keys here
        $resultMap = [];
        $resultMapKeys = preg_grep('/^\d+/', array_keys($data)) ?: [];
        foreach ($resultMapKeys as $key) {
            $resultMap[$key] = ($data[$key] ?? null) !== null ? AuthorMap::fromJson($data[$key]) : null;
        }
        return new self(
            $resultMap
        );
    }
}
