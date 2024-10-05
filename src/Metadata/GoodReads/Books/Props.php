<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

class Props
{
    public ?PageProps $pageProps;
    public ?bool $nSsp;

    public function __construct(?PageProps $pageProps, ?bool $nSsp)
    {
        $this->pageProps = $pageProps;
        $this->nSsp = $nSsp;
    }

    public function getPageProps(): ?PageProps
    {
        return $this->pageProps;
    }

    public function getNSsp(): ?bool
    {
        return $this->nSsp;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            ($data['pageProps'] ?? null) !== null ? PageProps::fromJson($data['pageProps']) : null,
            $data['__N_SSP'] ?? null
        );
    }
}
