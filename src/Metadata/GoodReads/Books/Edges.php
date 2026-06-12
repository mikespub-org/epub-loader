<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

class Edges
{
    public ?string $typename;
    public ?Node $node;

    public function __construct(?string $typename, ?Node $node)
    {
        $this->typename = $typename;
        $this->node = $node;
    }

    public function getTypename(): ?string
    {
        return $this->typename;
    }

    public function getNode(): ?Node
    {
        return $this->node;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['__typename'] ?? null,
            Mapper::getItem($data, 'node', Node::fromJson(...))
        );
    }
}
