<?php

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Entities;

/**
 * Use patternProperties here for any remote ids
 */
class RemoteIds
{
    //public ?string $wikidata;
    /** @var array<string, string>|null */
    public ?array $properties;

    /**
     * @param array<mixed> $properties
     */
    public function __construct(
        ?array $properties
        //?string $wikidata,
    ) {
        //$this->wikidata = $wikidata;
        $this->properties = $properties;
    }

    //public function getWikidata(): ?string
    //{
    //	return $this->wikidata;
    //}

    /**
     * @return array<string, string>|null
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        // simulate patternProperties from JSON schema - all keys here
        return new self(
            $data,
            //$data['wikidata'] ?? null,
        );
    }
}
