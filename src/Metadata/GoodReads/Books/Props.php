<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

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
        $keys = [
            'pageProps' => PageProps::fromJson(...),
            '__N_SSP' => null,
        ];

        return new self(...Mapper::getValues($data, $keys, self::class));
    }
}
