<?php

/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 * adapted for patternProperties in ApolloState and RootQuery - see JSON schema
 */

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

class BookResult
{
    public ?Props $props;
    public ?string $page;
    public ?Query $query;
    public ?string $buildId;
    public ?RuntimeConfig $runtimeConfig;
    public ?bool $isFallback;
    public ?bool $gssp;
    public ?bool $customServer;
    /** @var string[]|null */
    public ?array $locales;

    /**
     * @param string[]|null $locales
     */
    public function __construct(
        ?Props $props,
        ?string $page,
        ?Query $query,
        ?string $buildId,
        ?RuntimeConfig $runtimeConfig,
        ?bool $isFallback,
        ?bool $gssp,
        ?bool $customServer,
        ?array $locales
    ) {
        $this->props = $props;
        $this->page = $page;
        $this->query = $query;
        $this->buildId = $buildId;
        $this->runtimeConfig = $runtimeConfig;
        $this->isFallback = $isFallback;
        $this->gssp = $gssp;
        $this->customServer = $customServer;
        $this->locales = $locales;
    }

    public function getProps(): ?Props
    {
        return $this->props;
    }

    public function getPage(): ?string
    {
        return $this->page;
    }

    public function getQuery(): ?Query
    {
        return $this->query;
    }

    public function getBuildId(): ?string
    {
        return $this->buildId;
    }

    public function getRuntimeConfig(): ?RuntimeConfig
    {
        return $this->runtimeConfig;
    }

    public function getIsFallback(): ?bool
    {
        return $this->isFallback;
    }

    public function getGssp(): ?bool
    {
        return $this->gssp;
    }

    public function getCustomServer(): ?bool
    {
        return $this->customServer;
    }

    /**
     * @return string[]|null
     */
    public function getLocales(): ?array
    {
        return $this->locales;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            'props' => Props::fromJson(...),
            'page' => null,
            'query' => Query::fromJson(...),
            'buildId' => null,
            'runtimeConfig' => RuntimeConfig::fromJson(...),
            'isFallback' => null,
            'gssp' => null,
            'customServer' => null,
            'locales' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
