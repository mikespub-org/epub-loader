<?php

namespace Marsender\EPubLoader\Metadata\OpenLibrary\Search;

class AuthorDocs
{
    public ?string $key;
    public ?string $type;
    public ?string $name;
    /** @var string[]|null */
    public ?array $alternateNames;
    public ?string $topWork;
    public ?int $workCount;
    /** @var string[]|null */
    public ?array $topSubjects;
    public ?int $version;

    /**
     * @param string[]|null $alternateNames
     * @param string[]|null $topSubjects
     */
    public function __construct(
        ?string $key,
        ?string $type,
        ?string $name,
        ?array $alternateNames,
        ?string $topWork,
        ?int $workCount,
        ?array $topSubjects,
        ?int $version
    ) {
        $this->key = $key;
        $this->type = $type;
        $this->name = $name;
        $this->alternateNames = $alternateNames;
        $this->topWork = $topWork;
        $this->workCount = $workCount;
        $this->topSubjects = $topSubjects;
        $this->version = $version;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string[]|null
     */
    public function getAlternateNames(): ?array
    {
        return $this->alternateNames;
    }

    public function getTopWork(): ?string
    {
        return $this->topWork;
    }

    public function getWorkCount(): ?int
    {
        return $this->workCount;
    }

    /**
     * @return string[]|null
     */
    public function getTopSubjects(): ?array
    {
        return $this->topSubjects;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['key'] ?? null,
            $data['type'] ?? null,
            $data['name'] ?? null,
            $data['alternate_names'] ?? null,
            $data['top_work'] ?? null,
            $data['work_count'] ?? null,
            $data['top_subjects'] ?? null,
            $data['_version_'] ?? null
        );
    }
}
