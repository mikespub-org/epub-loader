<?php
/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 */

namespace Marsender\EPubLoader\Metadata\OpenLibrary;

class WorkEntity
{
    /** @todo some entities contain an array with ['type' => ..., 'value' => ...] */
    /** @var string|array<mixed>|null */
    public string|array|null $description;
    /** @var Links[]|null */
    public ?array $links;
    public ?string $title;
    /** @var int[]|null */
    public ?array $covers;
    /** @var string[]|null */
    public ?array $subjectPlaces;
    /** @var string[]|null */
    public ?array $subjects;
    public ?string $key;
    /** @var Authors[]|null */
    public ?array $authors;
    /** @var string[]|null */
    public ?array $subjectTimes;
    public ?Type $type;
    public ?int $latestRevision;
    public ?int $revision;
    public ?Created $created;
    public ?LastModified $lastModified;

    /**
     * @param string|array<mixed>|null $description
     * @param Links[]|null $links
     * @param int[]|null $covers
     * @param string[]|null $subjectPlaces
     * @param string[]|null $subjects
     * @param Authors[]|null $authors
     * @param string[]|null $subjectTimes
     */
    public function __construct(
        string|array|null $description,
        ?array $links,
        ?string $title,
        ?array $covers,
        ?array $subjectPlaces,
        ?array $subjects,
        ?string $key,
        ?array $authors,
        ?array $subjectTimes,
        ?Type $type,
        ?int $latestRevision,
        ?int $revision,
        ?Created $created,
        ?LastModified $lastModified
    ) {
        $this->description = $description;
        $this->links = $links;
        $this->title = $title;
        $this->covers = $covers;
        $this->subjectPlaces = $subjectPlaces;
        $this->subjects = $subjects;
        $this->key = $key;
        $this->authors = $authors;
        $this->subjectTimes = $subjectTimes;
        $this->type = $type;
        $this->latestRevision = $latestRevision;
        $this->revision = $revision;
        $this->created = $created;
        $this->lastModified = $lastModified;
    }

    /**
     * @return string|array<mixed>|null
     */
    public function getDescription(): string|array|null
    {
        return $this->description;
    }

    /**
     * @return Links[]|null
     */
    public function getLinks(): ?array
    {
        return $this->links;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return int[]|null
     */
    public function getCovers(): ?array
    {
        return $this->covers;
    }

    /**
     * @return string[]|null
     */
    public function getSubjectPlaces(): ?array
    {
        return $this->subjectPlaces;
    }

    /**
     * @return string[]|null
     */
    public function getSubjects(): ?array
    {
        return $this->subjects;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @return Authors[]|null
     */
    public function getAuthors(): ?array
    {
        return $this->authors;
    }

    /**
     * @return string[]|null
     */
    public function getSubjectTimes(): ?array
    {
        return $this->subjectTimes;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function getLatestRevision(): ?int
    {
        return $this->latestRevision;
    }

    public function getRevision(): ?int
    {
        return $this->revision;
    }

    public function getCreated(): ?Created
    {
        return $this->created;
    }

    public function getLastModified(): ?LastModified
    {
        return $this->lastModified;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['description'] ?? null,
            ($data['links'] ?? null) !== null ? array_map(static function ($data) {
                return Links::fromJson($data);
            }, $data['links']) : null,
            $data['title'] ?? null,
            $data['covers'] ?? null,
            $data['subject_places'] ?? null,
            $data['subjects'] ?? null,
            $data['key'] ?? null,
            ($data['authors'] ?? null) !== null ? array_map(static function ($data) {
                return Authors::fromJson($data);
            }, $data['authors']) : null,
            $data['subject_times'] ?? null,
            ($data['type'] ?? null) !== null ? Type::fromJson($data['type']) : null,
            $data['latest_revision'] ?? null,
            $data['revision'] ?? null,
            ($data['created'] ?? null) !== null ? Created::fromJson($data['created']) : null,
            ($data['last_modified'] ?? null) !== null ? LastModified::fromJson($data['last_modified']) : null
        );
    }
}
