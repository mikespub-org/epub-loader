<?php

/**
 * CalibreIdMapper class
 */

namespace Marsender\EPubLoader\Workflows\Converters;

use Marsender\EPubLoader\CalibreDbLoader;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsMatch;
use Marsender\EPubLoader\Metadata\GoogleBooks\GoogleBooksMatch;
use Marsender\EPubLoader\Metadata\OpenLibrary\OpenLibraryMatch;
use Marsender\EPubLoader\Metadata\WikiData\WikiDataMatch;
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Exception;

/**
 * Map info id to calibre id
 */
class CalibreIdMapper extends IdMapper
{
    /** @var CalibreDbLoader */
    protected $db;
    protected string $dbFileName;
    /** @var class-string */
    protected $matchClass;

    /**
     * Open a Calibre database file
     *
     * @param string $dbFileName Calibre database file name
     * @param string $identifierType Identifier type
     */
    public function __construct($dbFileName, $identifierType)
    {
        $this->dbFileName = $dbFileName;
        $this->matchClass = match ($identifierType) {
            'goodreads' => GoodReadsMatch::class,
            'google' => GoogleBooksMatch::class,
            'olid' => OpenLibraryMatch::class,
            'wd' => WikiDataMatch::class,
            default => throw new Exception('Invalid identifier type ' . $identifierType),
        };
        $this->db = new CalibreDbLoader($dbFileName);
        //$this->db->getNotesDb();
        $this->loadCalibreIdMaps($identifierType);
    }

    /**
     * Summary of loadCalibreIdMaps
     * @param string $type
     * @return void
     */
    protected function loadCalibreIdMaps($type)
    {
        // get all books without limit here
        $this->db->limit = -1;
        $bookLinks = $this->db->checkBookLinks($type);
        foreach ($bookLinks as $book) {
            if (empty($book['value'])) {
                continue;
            }
            $this->books[$book['value']] = $book['book'];
            if (!empty($book['author']) && !empty($book['author_link']) && ($this->matchClass)::isValidLink($book['author_link'])) {
                $matchId = ($this->matchClass)::entity($book['author_link']);
                $this->authors[$matchId] = $book['author'];
            }
            if (!empty($book['series']) && !empty($book['series_link']) && ($this->matchClass)::isValidLink($book['series_link'])) {
                $matchId = ($this->matchClass)::entity($book['series_link']);
                $this->series[$matchId] = $book['series'];
            }
        }
        // sort by 'books' to force no limit
        $authorList = $this->db->getAuthors(null, 'books');
        foreach ($authorList as $authorId => $author) {
            if (empty($author['link']) || !($this->matchClass)::isValidLink($author['link'])) {
                continue;
            }
            $matchId = ($this->matchClass)::entity($author['link']);
            $this->authors[$matchId] = $authorId;
        }
        $seriesLinks = $this->db->getSeriesLinks();
        foreach ($seriesLinks as $seriesId => $link) {
            if (empty($link) || !($this->matchClass)::isValidLink($link)) {
                continue;
            }
            $matchId = ($this->matchClass)::entity($link);
            $this->series[$matchId] = $seriesId;
        }
        $this->stats = [
            'authors' => count($this->authors),
            'books' => count($this->books),
            'series' => count($this->series),
            'hit' => 0,
            'miss' => 0,
        ];
    }
}
