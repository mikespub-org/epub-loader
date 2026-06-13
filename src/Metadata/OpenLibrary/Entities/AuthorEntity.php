<?php

/**
 * Based on https://jacobdekeizer.github.io/json-to-php-generator/
 */

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Entities;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            'title' => null,
            'source_records' => null,
            'personal_name' => null,
            'lc_classifications' => null,
            'subjects' => null,
            'birth_date' => null,
            'lccn' => null,
            'bio' => null,
            'alternate_names' => null,
            'remote_ids' => RemoteIds::fromJson(...),
            'authors' => [ AuthorKeys::fromJson(...) ],
            'death_date' => null,
            'links' => [ Links::fromJson(...) ],
            'type' => Type::fromJson(...),
            'works' => [ WorkKey::fromJson(...) ],
            'key' => null,
            'photos' => null,
            'name' => null,
            'latest_revision' => null,
            'revision' => null,
            'created' => Created::fromJson(...),
            'last_modified' => LastModified::fromJson(...),
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
