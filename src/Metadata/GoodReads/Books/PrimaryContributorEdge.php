<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            '__typename' => null,
            'node' => Node::fromJson(...),
            'role' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
