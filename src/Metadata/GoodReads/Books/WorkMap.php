<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

class WorkMap
{
    public ?string $id;
    public ?string $typename;
    public ?int $legacyId;
    public ?BestBook $bestBook;
    /** @var array<mixed>|null */
    public ?array $choiceAwards;
    public ?Details $details;
    public ?Stats $stats;
    public ?Quotes $quotes;
    public ?Questions $questions;
    public ?Topics $topics;
    public mixed $viewerShelvings;
    public ?string $viewerShelvingsUrl;
    public ?FeaturedKnh $featuredKNH;
    public mixed $giveaways;
    public ?Editions $editions;

    /**
     * @param array<mixed>|null $choiceAwards
     */
    public function __construct(
        ?string $id,
        ?string $typename,
        ?int $legacyId,
        ?BestBook $bestBook,
        ?array $choiceAwards,
        ?Details $details,
        ?Stats $stats,
        ?Quotes $quotes,
        ?Questions $questions,
        ?Topics $topics,
        mixed $viewerShelvings,
        ?string $viewerShelvingsUrl,
        ?FeaturedKnh $featuredKNH,
        mixed $giveaways,
        ?Editions $editions
    ) {
        $this->id = $id;
        $this->typename = $typename;
        $this->legacyId = $legacyId;
        $this->bestBook = $bestBook;
        $this->choiceAwards = $choiceAwards;
        $this->details = $details;
        $this->stats = $stats;
        $this->quotes = $quotes;
        $this->questions = $questions;
        $this->topics = $topics;
        $this->viewerShelvings = $viewerShelvings;
        $this->viewerShelvingsUrl = $viewerShelvingsUrl;
        $this->featuredKNH = $featuredKNH;
        $this->giveaways = $giveaways;
        $this->editions = $editions;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getLegacyId(): ?int
    {
        return $this->legacyId;
    }

    public function getBestBook(): ?BestBook
    {
        return $this->bestBook;
    }

    /**
     * @return array<mixed>|null
     */
    public function getChoiceAwards(): ?array
    {
        return $this->choiceAwards;
    }

    public function getDetails(): ?Details
    {
        return $this->details;
    }

    public function getStats(): ?Stats
    {
        return $this->stats;
    }

    /**
     * quotes({\"pagination\":{\"limit\":1}}) = one per book
     */
    public function getQuotes(): ?Quotes
    {
        return $this->quotes;
    }

    /**
     * questions({\"pagination\":{\"limit\":1}}) = one per book
     */
    public function getQuestions(): ?Questions
    {
        return $this->questions;
    }

    /**
     * topics({\"pagination\":{\"limit\":1}}) = one per book
     */
    public function getTopics(): ?Topics
    {
        return $this->topics;
    }

    public function getViewerShelvings(): mixed
    {
        return $this->viewerShelvings;
    }

    public function getViewerShelvingsUrl(): ?string
    {
        return $this->viewerShelvingsUrl;
    }

    public function getFeaturedKnh(): ?FeaturedKnh
    {
        return $this->featuredKNH;
    }

    public function getGiveaways(): mixed
    {
        return $this->giveaways;
    }

    public function getEditions(): ?Editions
    {
        return $this->editions;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        // simulate patternProperties from JSON schema - single key here
        // quotes({\"pagination\":{\"limit\":1}}) = one per book
        // questions({\"pagination\":{\"limit\":1}}) = one per book
        // topics({\"pagination\":{\"limit\":1}}) = one per book
        $keys = [
            'id'                => null,
            '__typename'        => null,
            'legacyId'          => null,
            'bestBook'          => BestBook::fromJson(...),
            'choiceAwards'      => null,
            'details'           => Details::fromJson(...),
            'stats'             => Stats::fromJson(...),
            '/^quotes\(/'       => Quotes::fromJson(...),
            '/^questions\(/'    => Questions::fromJson(...),
            '/^topics\(/'       => Topics::fromJson(...),
            'viewerShelvings'   => null,
            'viewerShelvingsUrl' => null,
            'featuredKNH'       => FeaturedKnh::fromJson(...),
            'giveaways'         => null,
            'editions'          => Editions::fromJson(...),
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
