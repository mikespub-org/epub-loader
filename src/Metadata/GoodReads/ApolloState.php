<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class ApolloState
{
    public ?RootQuery $rootQuery;
    /** @var array<string, ContributorMap>|null */
    public ?array $contributorMap;
    /** @var array<string, SeriesMap>|null */
    public ?array $seriesMap;
    /** @var array<string, BookMap>|null */
    public ?array $bookMap;
    /** @var array<string, WorkMap>|null */
    public ?array $workMap;
    /** @var array<string, UserMap>|null */
    public ?array $userMap;
    /** @var array<string, ReviewMap>|null */
    public ?array $reviewMap;

    /**
     * @param array<string, ContributorMap>|null $contributorMap
     * @param array<string, SeriesMap>|null $seriesMap
     * @param array<string, BookMap>|null $bookMap
     * @param array<string, WorkMap>|null $workMap
     * @param array<string, UserMap>|null $userMap
     * @param array<string, ReviewMap>|null $reviewMap
     */
    public function __construct(
        ?RootQuery $rootQuery,
        ?array $contributorMap,
        ?array $seriesMap,
        ?array $bookMap,
        ?array $workMap,
        ?array $userMap,
        ?array $reviewMap
    ) {
        $this->rootQuery = $rootQuery;
        $this->contributorMap = $contributorMap;
        $this->seriesMap = $seriesMap;
        $this->bookMap = $bookMap;
        $this->workMap = $workMap;
        $this->userMap = $userMap;
        $this->reviewMap = $reviewMap;
    }

    public function getRootQuery(): ?RootQuery
    {
        return $this->rootQuery;
    }

    /**
     * @return array<string, ContributorMap>|null
     */
    public function getContributorMap(): ?array
    {
        return $this->contributorMap;
    }

    /**
     * @return array<string, SeriesMap>|null
     */
    public function getSeriesMap(): ?array
    {
        return $this->seriesMap;
    }

    /**
     * @return array<string, BookMap>|null
     */
    public function getBookMap(): ?array
    {
        return $this->bookMap;
    }

    /**
     * @return array<string, WorkMap>|null
     */
    public function getWorkMap(): ?array
    {
        return $this->workMap;
    }

    /**
     * @return array<string, UserMap>|null
     */
    public function getUserMap(): ?array
    {
        return $this->userMap;
    }

    /**
     * @return array<string, ReviewMap>|null
     */
    public function getReviewMap(): ?array
    {
        return $this->reviewMap;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        // simulate patternProperties from JSON schema - multiple keys here
        $contributorMap = [];
        $contributorMapKeys = preg_grep('/^Contributor:/', array_keys($data)) ?: [];
        foreach ($contributorMapKeys as $key) {
            $contributorMap[$key] = ($data[$key] ?? null) !== null ? ContributorMap::fromJson($data[$key]) : null;
        }
        $seriesMap = [];
        $seriesMapKeys = preg_grep('/^Series:/', array_keys($data)) ?: [];
        foreach ($seriesMapKeys as $key) {
            $seriesMap[$key] = ($data[$key] ?? null) !== null ? SeriesMap::fromJson($data[$key]) : null;
        }
        $bookMap = [];
        $bookMapKeys = preg_grep('/^Book:/', array_keys($data)) ?: [];
        foreach ($bookMapKeys as $key) {
            $bookMap[$key] = ($data[$key] ?? null) !== null ? BookMap::fromJson($data[$key]) : null;
        }
        $workMap = [];
        $workMapKeys = preg_grep('/^Work:/', array_keys($data)) ?: [];
        foreach ($workMapKeys as $key) {
            $workMap[$key] = ($data[$key] ?? null) !== null ? WorkMap::fromJson($data[$key]) : null;
        }
        $userMap = [];
        $userMapKeys = preg_grep('/^User:/', array_keys($data)) ?: [];
        foreach ($userMapKeys as $key) {
            $userMap[$key] = ($data[$key] ?? null) !== null ? UserMap::fromJson($data[$key]) : null;
        }
        $reviewMap = [];
        $reviewMapKeys = preg_grep('/^Review:/', array_keys($data)) ?: [];
        foreach ($reviewMapKeys as $key) {
            $reviewMap[$key] = ($data[$key] ?? null) !== null ? ReviewMap::fromJson($data[$key]) : null;
        }
        return new self(
            ($data['ROOT_QUERY'] ?? null) !== null ? RootQuery::fromJson($data['ROOT_QUERY']) : null,
            $contributorMap,
            $seriesMap,
            $bookMap,
            $workMap,
            $userMap,
            $reviewMap
        );
    }
}
