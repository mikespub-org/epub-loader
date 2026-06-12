<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

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
            $contributorMap[$key] = Mapper::getItem($data, $key, ContributorMap::fromJson(...));
        }
        $seriesMap = [];
        $seriesMapKeys = preg_grep('/^Series:/', array_keys($data)) ?: [];
        foreach ($seriesMapKeys as $key) {
            $seriesMap[$key] = Mapper::getItem($data, $key, SeriesMap::fromJson(...));
        }
        $bookMap = [];
        $bookMapKeys = preg_grep('/^Book:/', array_keys($data)) ?: [];
        foreach ($bookMapKeys as $key) {
            $bookMap[$key] = Mapper::getItem($data, $key, BookMap::fromJson(...));
        }
        $workMap = [];
        $workMapKeys = preg_grep('/^Work:/', array_keys($data)) ?: [];
        foreach ($workMapKeys as $key) {
            $workMap[$key] = Mapper::getItem($data, $key, WorkMap::fromJson(...));
        }
        $userMap = [];
        $userMapKeys = preg_grep('/^User:/', array_keys($data)) ?: [];
        foreach ($userMapKeys as $key) {
            $userMap[$key] = Mapper::getItem($data, $key, UserMap::fromJson(...));
        }
        $reviewMap = [];
        $reviewMapKeys = preg_grep('/^Review:/', array_keys($data)) ?: [];
        foreach ($reviewMapKeys as $key) {
            $reviewMap[$key] = Mapper::getItem($data, $key, ReviewMap::fromJson(...));
        }
        return new self(
            Mapper::getItem($data, 'ROOT_QUERY', RootQuery::fromJson(...)),
            $contributorMap,
            $seriesMap,
            $bookMap,
            $workMap,
            $userMap,
            $reviewMap
        );
    }
}
