<?php
/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 */

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Entities;

class AuthorEntity
{
    public ?string $title;
    /** @var string[]|null */
    public ?array $sourceRecords;
    public ?string $personalName;
    /** @var string[]|null */
    public ?array $lcClassifications;
    /** @var string[]|null */
    public ?array $subjects;
    public ?string $birthDate;
    /** @var string[]|null */
    public ?array $lccn;
    /** @todo some entities contain an array with ['type' => ..., 'value' => ...] */
    /** @var string|array<mixed>|null */
    public string|array|null $bio;
    /** @var string[]|null */
    public ?array $alternateNames;
    public ?RemoteIds $remoteIds;
    /** @var AuthorKeys[]|null */
    public ?array $authors;
    public ?string $deathDate;
    /** @var Links[]|null */
    public ?array $links;
    public ?Type $type;
    /** @var WorkKey[]|null */
    public ?array $works;
    public ?string $key;
    /** @var int[]|null */
    public ?array $photos;
    public ?string $name;
    public ?int $latestRevision;
    public ?int $revision;
    public ?Created $created;
    public ?LastModified $lastModified;

    /**
     * @param string[]|null $sourceRecords
     * @param string[]|null $lcClassifications
     * @param string[]|null $subjects
     * @param string[]|null $lccn
     * @param string|array<mixed>|null $bio
     * @param string[]|null $alternateNames
     * @param AuthorKeys[]|null $authors
     * @param Links[]|null $links
     * @param WorkKey[]|null $works
     * @param int[]|null $photos
     */
    public function __construct(
        ?string $title,
        ?array $sourceRecords,
        ?string $personalName,
        ?array $lcClassifications,
        ?array $subjects,
        ?string $birthDate,
        ?array $lccn,
        string|array|null $bio,
        ?array $alternateNames,
        ?RemoteIds $remoteIds,
        ?array $authors,
        ?string $deathDate,
        ?array $links,
        ?Type $type,
        ?array $works,
        ?string $key,
        ?array $photos,
        ?string $name,
        ?int $latestRevision,
        ?int $revision,
        ?Created $created,
        ?LastModified $lastModified
    ) {
        $this->title = $title;
        $this->sourceRecords = $sourceRecords;
        $this->personalName = $personalName;
        $this->lcClassifications = $lcClassifications;
        $this->subjects = $subjects;
        $this->birthDate = $birthDate;
        $this->lccn = $lccn;
        $this->bio = $bio;
        $this->alternateNames = $alternateNames;
        $this->remoteIds = $remoteIds;
        $this->authors = $authors;
        $this->deathDate = $deathDate;
        $this->links = $links;
        $this->type = $type;
        $this->works = $works;
        $this->key = $key;
        $this->photos = $photos;
        $this->name = $name;
        $this->latestRevision = $latestRevision;
        $this->revision = $revision;
        $this->created = $created;
        $this->lastModified = $lastModified;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return string[]|null
     */
    public function getSourceRecords(): ?array
    {
        return $this->sourceRecords;
    }

    public function getPersonalName(): ?string
    {
        return $this->personalName;
    }

    /**
     * @return string[]|null
     */
    public function getLcClassifications(): ?array
    {
        return $this->lcClassifications;
    }

    /**
     * @return string[]|null
     */
    public function getSubjects(): ?array
    {
        return $this->subjects;
    }

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    /**
     * @return string[]|null
     */
    public function getLccn(): ?array
    {
        return $this->lccn;
    }

    /**
     * @return string|array<mixed>|null
     */
    public function getBio(): string|array|null
    {
        return $this->bio;
    }

    /**
     * @return string[]|null
     */
    public function getAlternateNames(): ?array
    {
        return $this->alternateNames;
    }

    public function getRemoteIds(): ?RemoteIds
    {
        return $this->remoteIds;
    }

    /**
     * @return AuthorKeys[]|null
     */
    public function getAuthors(): ?array
    {
        return $this->authors;
    }

    public function getDeathDate(): ?string
    {
        return $this->deathDate;
    }

    /**
     * @return Links[]|null
     */
    public function getLinks(): ?array
    {
        return $this->links;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    /**
     * @return WorkKey[]|null
     */
    public function getWorks(): ?array
    {
        return $this->works;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @return int[]|null
     */
    public function getPhotos(): ?array
    {
        return $this->photos;
    }

    public function getName(): ?string
    {
        return $this->name;
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
            $data['title'] ?? null,
            $data['source_records'] ?? null,
            $data['personal_name'] ?? null,
            $data['lc_classifications'] ?? null,
            $data['subjects'] ?? null,
            $data['birth_date'] ?? null,
            $data['lccn'] ?? null,
            $data['bio'] ?? null,
            $data['alternate_names'] ?? null,
            ($data['remote_ids'] ?? null) !== null ? RemoteIds::fromJson($data['remote_ids']) : null,
            ($data['authors'] ?? null) !== null ? array_map(static function ($data) {
                return AuthorKeys::fromJson($data);
            }, $data['authors']) : null,
            $data['death_date'] ?? null,
            ($data['links'] ?? null) !== null ? array_map(static function ($data) {
                return Links::fromJson($data);
            }, $data['links']) : null,
            ($data['type'] ?? null) !== null ? Type::fromJson($data['type']) : null,
            ($data['works'] ?? null) !== null ? array_map(static function ($data) {
                return WorkKey::fromJson($data);
            }, $data['works']) : null,
            $data['key'] ?? null,
            $data['photos'] ?? null,
            $data['name'] ?? null,
            $data['latest_revision'] ?? null,
            $data['revision'] ?? null,
            ($data['created'] ?? null) !== null ? Created::fromJson($data['created']) : null,
            ($data['last_modified'] ?? null) !== null ? LastModified::fromJson($data['last_modified']) : null
        );
    }
}
