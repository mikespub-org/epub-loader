<?php
/**
 * Load info from series JSON file (row format)
 */

namespace Marsender\EPubLoader\Metadata\GoodReads\Series;

use Exception;

class SeriesResult
{
    public ?string $id;
    public ?string $title;
    public ?string $subtitle;
    public ?string $description;
    public ?int $numWorks;
    /** @var Book[]|null */
    public ?array $bookList;

    /**
     * @param Book[]|null $bookList
     */
    public function __construct(
        ?string $id,
        ?string $title,
        ?string $subtitle,
        ?string $description,
        ?int $numWorks,
        ?array $bookList
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->description = $description;
        $this->numWorks = $numWorks;
        $this->bookList = $bookList;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getNumWorks(): ?int
    {
        return $this->numWorks;
    }

    /**
     * @return Book[]|null
     */
    public function getBookList(): ?array
    {
        return $this->bookList;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJson(array $data): self
    {
        // id is not available in JSON data - this must be set by caller
        $info = [
            'id' => null,
            'title' => null,
            'subtitle' => null,
            'description' => null,
            'numWorks' => null,
            'bookList' => [],
        ];
        // convert row format by type - we can have several SeriesList here
        foreach ($data as $row) {
            switch ($row[0]) {
                case "ReactComponents.SeriesHeader":
                    $header = SeriesHeader::fromJson($row[1]);
                    $info['title'] = $header->getTitle();
                    $info['subtitle'] = $header->getSubtitle();
                    $info['description'] = $header->getDescription()->getHtml();
                    break;
                case "ReactComponents.SeriesList":
                    $list = SeriesList::fromJson($row[1]);
                    $series = $list->getSeries() ?? [];
                    $headers = $list->getSeriesHeaders() ?? [];
                    foreach ($series as $id => $serie) {
                        $book = $serie->getBook();
                        $book->setSeriesHeader($headers[$id]);
                        $info['bookList'][] = $book;
                    }
                    break;
                case "ReactComponents.FullPagePaginationControls":
                    $pagination = PaginationControls::fromJson($row[1]);
                    $info['numWorks'] = $pagination->getNumWorks();
                    break;
                default:
                    throw new Exception('Unknown series row format');
            }
        }
        return new self(
            $info['id'],
            $info['title'],
            $info['subtitle'],
            $info['description'],
            $info['numWorks'],
            $info['bookList']
        );
    }
}
