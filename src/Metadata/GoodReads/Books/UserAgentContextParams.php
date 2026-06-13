<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            'isWebView' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
