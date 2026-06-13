<?php

namespace Marsender\EPubLoader\Metadata\GoogleBooks\Volumes;

use Marsender\EPubLoader\Metadata\Mapper;

class AccessInfo
{
    public ?string $country;
    public ?string $viewability;
    public ?bool $embeddable;
    public ?bool $publicDomain;
    public ?string $textToSpeechPermission;
    public ?Epub $epub;
    public ?Pdf $pdf;
    public ?string $webReaderLink;
    public ?string $accessViewStatus;
    public ?bool $quoteSharingAllowed;

    public function __construct(
        ?string $country,
        ?string $viewability,
        ?bool $embeddable,
        ?bool $publicDomain,
        ?string $textToSpeechPermission,
        ?Epub $epub,
        ?Pdf $pdf,
        ?string $webReaderLink,
        ?string $accessViewStatus,
        ?bool $quoteSharingAllowed
    ) {
        $this->country = $country;
        $this->viewability = $viewability;
        $this->embeddable = $embeddable;
        $this->publicDomain = $publicDomain;
        $this->textToSpeechPermission = $textToSpeechPermission;
        $this->epub = $epub;
        $this->pdf = $pdf;
        $this->webReaderLink = $webReaderLink;
        $this->accessViewStatus = $accessViewStatus;
        $this->quoteSharingAllowed = $quoteSharingAllowed;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getViewability(): ?string
    {
        return $this->viewability;
    }

    public function getEmbeddable(): ?bool
    {
        return $this->embeddable;
    }

    public function getPublicDomain(): ?bool
    {
        return $this->publicDomain;
    }

    public function getTextToSpeechPermission(): ?string
    {
        return $this->textToSpeechPermission;
    }

    public function getEpub(): ?Epub
    {
        return $this->epub;
    }

    public function getPdf(): ?Pdf
    {
        return $this->pdf;
    }

    public function getWebReaderLink(): ?string
    {
        return $this->webReaderLink;
    }

    public function getAccessViewStatus(): ?string
    {
        return $this->accessViewStatus;
    }

    public function getQuoteSharingAllowed(): ?bool
    {
        return $this->quoteSharingAllowed;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        $keys = [
            'country' => null,
            'viewability' => null,
            'embeddable' => null,
            'publicDomain' => null,
            'textToSpeechPermission' => null,
            'epub' => Epub::fromJson(...),
            'pdf' => Pdf::fromJson(...),
            'webReaderLink' => null,
            'accessViewStatus' => null,
            'quoteSharingAllowed' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
