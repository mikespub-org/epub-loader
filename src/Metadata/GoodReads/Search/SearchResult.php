<?php
/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 * adapted for patternProperties in SearchResult - see JSON schema
 */

namespace Marsender\EPubLoader\Metadata\GoodReads\Search;

class SearchResult
{
    /** @var array<string, AuthorEntry>|null */
    public ?array $properties;

    /**
     * @param array<string, AuthorEntry>|null $properties
     */
    public function __construct(?array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * @return array<string, AuthorEntry>|null
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function getAuthorEntry(string $key): ?AuthorEntry
    {
        return $this->properties[$key] ?? null;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        // simulate patternProperties from JSON schema - all keys here
        $properties = [];
        $propertiesKeys = preg_grep('/^\d+/', array_keys($data)) ?: [];
        foreach ($propertiesKeys as $key) {
            $properties[$key] = ($data[$key] ?? null) !== null ? AuthorEntry::fromJson($data[$key]) : null;
        }
        return new self(
            $properties
        );
    }
}
