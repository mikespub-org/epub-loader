<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

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
    public ?FeaturedKnh $featuredKnh;
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
        ?FeaturedKnh $featuredKnh,
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
        $this->featuredKnh = $featuredKnh;
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
        return $this->featuredKnh;
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
        $quotesKeys = preg_grep('/^quotes\\(/', array_keys($data)) ?: [''];
        $quotesKey = reset($quotesKeys);
        // questions({\"pagination\":{\"limit\":1}}) = one per book
        $questionsKeys = preg_grep('/^questions\\(/', array_keys($data)) ?: [''];
        $questionsKey = reset($questionsKeys);
        // topics({\"pagination\":{\"limit\":1}}) = one per book
        $topicsKeys = preg_grep('/^topics\\(/', array_keys($data)) ?: [''];
        $topicsKey = reset($topicsKeys);
        return new self(
            $data['id'] ?? null,
            $data['__typename'] ?? null,
            $data['legacyId'] ?? null,
            ($data['bestBook'] ?? null) !== null ? BestBook::fromJson($data['bestBook']) : null,
            $data['choiceAwards'] ?? null,
            ($data['details'] ?? null) !== null ? Details::fromJson($data['details']) : null,
            ($data['stats'] ?? null) !== null ? Stats::fromJson($data['stats']) : null,
            ($data[$quotesKey] ?? null) !== null ? Quotes::fromJson($data[$quotesKey]) : null,
            ($data[$questionsKey] ?? null) !== null ? Questions::fromJson($data[$questionsKey]) : null,
            ($data[$topicsKey] ?? null) !== null ? Topics::fromJson($data[$topicsKey]) : null,
            $data['viewerShelvings'] ?? null,
            $data['viewerShelvingsUrl'] ?? null,
            ($data['featuredKNH'] ?? null) !== null ? FeaturedKnh::fromJson($data['featuredKNH']) : null,
            $data['giveaways'] ?? null,
            ($data['editions'] ?? null) !== null ? Editions::fromJson($data['editions']) : null
        );
    }
}
