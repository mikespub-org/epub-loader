<?php

namespace Marsender\EPubLoader\Metadata\GoodReads;

class UserAgentContextParams
{
    public null $isWebView;

    public function __construct(null $isWebView)
    {
        $this->isWebView = $isWebView;
    }

    public function getIsWebView(): null
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
