<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class PrimaryContributorEdge
{
    public ?string $typename;
    public ?Node $node;
    public ?string $role;

    public function __construct(
        ?string $typename,
        ?Node $node,
        ?string $role
    ) {
        $this->typename = $typename;
        $this->node = $node;
        $this->role = $role;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getNode(): ?Node
    {
        return $this->node;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            ($data['node'] ?? null) !== null ? Node::fromJson($data['node']) : null,
            $data['role'] ?? null
        );
    }
}
