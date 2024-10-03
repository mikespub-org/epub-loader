<?php

namespace Marsender\EPubLoader\Metadata\OpenLibrary;

/**
 * Use patternProperties here for any remote ids
 */
class RemoteIds
{
    //public ?string $wikidata;
    /** @var array<string, string>|null */
    public ?array $map;

    /**
     * @param array<mixed> $map
     */
    public function __construct(
        ?array $map
        //?string $wikidata,
    ) {
        //$this->wikidata = $wikidata;
        $this->map = $map;
    }

    //public function getWikidata(): ?string
    //{
    //	return $this->wikidata;
    //}

    /**
     * @return array<string, string>|null
     */
    public function getMap(): ?array
    {
        return $this->map;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        // use patternProperties here for any remote ids
        return new self(
            $data,
            //$data['wikidata'] ?? null,
        );
    }
}
