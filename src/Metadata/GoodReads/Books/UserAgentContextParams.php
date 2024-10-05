<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class UserAgentContextParams
{
    public mixed $isWebView;

    public function __construct(mixed $isWebView)
    {
        $this->isWebView = $isWebView;
    }

    public function getIsWebView(): mixed
    {
        return $this->isWebView;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            $data['isWebView'] ?? null
        );
    }
}
