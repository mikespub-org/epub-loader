<?php

namespace Marsender\EPubLoader\Metadata\GoodReads\Books;

use Marsender\EPubLoader\Metadata\Mapper;

class Props
{
    public ?PageProps $pageProps;
    public ?bool $nSSP;

    public function __construct(?PageProps $pageProps, ?bool $nSSP)
    {
        $this->pageProps = $pageProps;
        $this->nSSP = $nSSP;
    }

    public function getPageProps(): ?PageProps
    {
        return $this->pageProps;
    }

    public function getNSSP(): ?bool
    {
        return $this->nSSP;
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
