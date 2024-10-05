<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class Places
{
    public ?string $typename;
    public ?string $name;
    public ?string $countryName;
    public ?string $webUrl;
    public mixed $year;

    public function __construct(
        ?string $typename,
        ?string $name,
        ?string $countryName,
        ?string $webUrl,
        mixed $year
    ) {
        $this->typename = $typename;
        $this->name = $name;
        $this->countryName = $countryName;
        $this->webUrl = $webUrl;
        $this->year = $year;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function getWebUrl(): ?string
    {
        return $this->webUrl;
    }

    public function getYear(): mixed
    {
        return $this->year;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            $data['name'] ?? null,
            $data['countryName'] ?? null,
            $data['webUrl'] ?? null,
            $data['year'] ?? null
        );
    }
}
