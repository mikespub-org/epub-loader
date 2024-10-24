<?php
/**
 * GoodReadsImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\GoodReads;

use Marsender\EPubLoader\Metadata\AuthorInfo;
use Marsender\EPubLoader\Metadata\BookInfo;
use Marsender\EPubLoader\Metadata\SeriesInfo;
use Marsender\EPubLoader\Metadata\GoodReads\Books\BookResult;
use Marsender\EPubLoader\Metadata\GoodReads\Search\SearchResult;
use Marsender\EPubLoader\Metadata\GoodReads\Series\SeriesResult;
use Marsender\EPubLoader\Metadata\GoodReads\Series\Book as SeriesBook;
use Exception;

class GoodReadsImport
{
    /**
     * Loads book infos from a GoodReads book
     *
     * @param string $basePath base directory
     * @param BookResult $bookResult GoodReads book show
     * @throws Exception if error
     *
     * @return BookInfo
     */
    public static function load($basePath, $bookResult)
    {
        $state = $bookResult->getProps()?->getPageProps()?->getApolloState();
        if (empty($state)) {
            throw new Exception('Invalid state for GoodReads book');
        }
        $bookRef = $state->getRootQuery()?->getGetBookByLegacyId()?->getRef();
        $bookMap = $state->getBookMap();
        if (empty($bookRef) || empty($bookMap) || empty($bookMap[$bookRef])) {
            throw new Exception('Invalid bookRef for GoodReads book');
        }
        $book = $bookMap[$bookRef];
        $workRef = $book->getWork()?->getRef();
        $workMap = $state->getWorkMap();
        if (empty($workRef) || empty($workMap) || empty($workMap[$workRef])) {
            throw new Exception('Invalid workRef for GoodReads book');
        }
        $work = $workMap[$workRef];

        $bookInfo = new BookInfo();
        $bookInfo->source = 'goodreads';
        $bookInfo->basePath = $basePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfo->format = 'epub';
        // @todo use calibre_external_storage in COPS
        $bookInfo->path = (string) $book->getWebUrl();
        if (str_starts_with($bookInfo->path, $basePath)) {
            $bookInfo->path = substr($bookInfo->path, strlen($basePath) + 1);
        }
        $bookInfo->name = (string) $book->getLegacyId();
        if (!empty($bookInfo->name)) {
            $bookInfo->uuid = 'goodreads:' . $bookInfo->name;
        } else {
            $bookInfo->createUuid();
            $bookInfo->name = $bookInfo->uuid;
        }
        $bookInfo->uri = (string) $book->getWebUrl();
        $bookInfo->title = (string) $book->getTitle();
        $authors = [];
        $authorRef = $book->getPrimaryContributorEdge()?->getNode()?->getRef();
        $contributors = $state->getContributorMap();
        if (empty($authorRef) || empty($contributors) || empty($contributors[$authorRef])) {
            throw new Exception('Invalid authorRef for GoodReads book');
        }
        $author = (string) $contributors[$authorRef]->getName();
        $authorSort = AuthorInfo::getNameSort($author);
        $authors[$authorSort] = $author;
        $authorId = str_replace('https://www.goodreads.com/author/show/', '', (string) $contributors[$authorRef]->getWebUrl());
        $bookInfo->authorIds = [];
        if (!empty($authorId)) {
            $bookInfo->authorIds[] = $authorId;
        }
        // add authors from secondaryContributorEdges if they are Author
        $others = $book->getSecondaryContributorEdges() ?? [];
        foreach ($others as $edge) {
            // ignore others like Editor, Afterword, Tradutor etc.
            if (empty($edge['role']) || $edge['role'] != 'Author') {
                continue;
            }
            $authorRef = $edge['node']['__ref'];
            if (empty($contributors[$authorRef])) {
                throw new Exception('Invalid secondary authorRef for GoodReads book: ' . $authorRef);
            }
            $author = (string) $contributors[$authorRef]->getName();
            $authorSort = AuthorInfo::getNameSort($author);
            $authors[$authorSort] = $author;
            $authorId = str_replace('https://www.goodreads.com/author/show/', '', (string) $contributors[$authorRef]->getWebUrl());
            if (!empty($authorId)) {
                $bookInfo->authorIds[] = $authorId;
            }
        }
        $bookInfo->authors = $authors;
        $bookInfo->language = (string) $book->getDetails()?->getLanguage()?->getName();
        $bookInfo->description = (string) $book->getDescription();
        $subjects = [];
        $bookGenres = $book->getBookGenres() ?? [];
        foreach ($bookGenres as $bookGenre) {
            $subject = $bookGenre->getGenre()?->getName();
            if (empty($subject)) {
                continue;
            }
            $subjects[] = (string) $subject;
        }
        $bookInfo->subjects = $subjects;
        $bookInfo->cover = (string) $book->getImageUrl();
        $isbn = $book->getDetails()?->getIsbn13();
        if (empty($isbn)) {
            $isbn = $book->getDetails()?->getIsbn();
        }
        if (!empty($isbn)) {
            $bookInfo->isbn = (string) $isbn;
        }
        //$bookInfo->rights = $array[$i++];
        $bookInfo->publisher = (string) $book->getDetails()?->getPublisher();
        $seriesMap = $state->getSeriesMap();
        $bookSeries = $book->getBookSeries() ?? [];
        $bookInfo->serieIds = [];
        foreach ($bookSeries as $series) {
            $seriesRef = $series->getSeries()?->getRef();
            if (empty($seriesRef) || empty($seriesMap) || empty($seriesMap[$seriesRef])) {
                throw new Exception('Invalid seriesRef for GoodReads book');
            }
            // use only the 1st series for name & index here
            if ($bookInfo->serieIndex == '') {
                $bookInfo->serieIndex = (string) $series->getUserPosition();
            }
            if ($bookInfo->serie == '') {
                $bookInfo->serie = (string) $seriesMap[$seriesRef]->getTitle();
            }
            // save ids of the other series here for matching?
            $bookInfo->serieIds[] = str_replace('https://www.goodreads.com/series/', '', (string) $seriesMap[$seriesRef]->getWebUrl());
        }
        // timestamp in milliseconds since the epoch for Javascript
        $timestamp = $book->getDetails()?->getPublicationTime() ?? $work->getDetails()?->getPublicationTime();
        if (!empty($timestamp)) {
            // format as '@timestamp' in seconds since the epoch for DateTime()
            $timestamp = '@' . (string) intval($timestamp / 1000);
        }
        $bookInfo->creationDate = (string) BookInfo::getSqlDate($timestamp);
        // @todo no modification date here
        $bookInfo->modificationDate = $bookInfo->creationDate;
        // Timestamp is used to get latest ebooks
        $bookInfo->timeStamp = $bookInfo->creationDate;
        $bookInfo->rating = $work->getStats()?->getAverageRating();
        $bookInfo->identifiers = ['goodreads' => $bookInfo->name];
        if (!empty($bookInfo->isbn)) {
            $bookInfo->identifiers['isbn'] = $bookInfo->isbn;
        }

        return $bookInfo;
    }

    /**
     * Loads book infos from a GoodReads series
     *
     * @param string $basePath base directory
     * @param SeriesResult $seriesResult GoodReads series
     * @throws Exception if error
     *
     * @return array<mixed>
     */
    public static function loadSeries($basePath, $seriesResult)
    {
        $series = (array) $seriesResult;
        foreach ($seriesResult->getBookList() as $key => $book) {
            if (empty($book->getBookId())) {
                continue;
            }
            // convert to BookInfo
            $bookInfo = self::loadSeriesBook($basePath, $book);
            $bookInfo->serie = $seriesResult->getTitle();
            $bookInfo->serieIds = [ $seriesResult->getId() ];
            $series['bookList'][$key] = $bookInfo;
        }
        return $series;
    }

    /**
     * Loads book infos from a GoodReads series book
     *
     * @param string $basePath base directory
     * @param SeriesBook $book GoodReads series book
     * @throws Exception if error
     *
     * @return BookInfo
     */
    public static function loadSeriesBook($basePath, $book)
    {
        $bookInfo = new BookInfo();
        $bookInfo->source = 'goodreads';
        $bookInfo->basePath = $basePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfo->format = 'epub';
        // @todo use calibre_external_storage in COPS
        $bookInfo->path = (string) $book->getBookUrl();
        if (str_starts_with($bookInfo->path, '/book/show/')) {
            $bookInfo->path = 'https://www.goodreads.com' . $bookInfo->path;
        }
        if (str_starts_with($bookInfo->path, $basePath)) {
            $bookInfo->path = substr($bookInfo->path, strlen($basePath) + 1);
        }
        $bookInfo->name = (string) $book->getBookId();
        if (!empty($bookInfo->name)) {
            $bookInfo->uuid = 'goodreads:' . $bookInfo->name;
        } else {
            $bookInfo->createUuid();
            $bookInfo->name = $bookInfo->uuid;
        }
        $bookInfo->uri = $bookInfo->path;
        $bookInfo->title = (string) ($book->getBookTitleBare() ?? $book->getTitle());
        $author = $book->getAuthor();
        if (!empty($author) && !empty($author->getId())) {
            $authorName = $author->getName();
            $authorSort = AuthorInfo::getNameSort($authorName);
            $bookInfo->authors = [ $authorSort => $authorName ];
            if (str_starts_with($author->getWorksListUrl(), GoodReadsMatch::AUTHOR_URL)) {
                $authorId = str_replace(GoodReadsMatch::AUTHOR_URL, '', $author->getWorksListUrl());
                $bookInfo->authorIds = [ $authorId ];
            }
        }
        $bookInfo->description = (string) $book->getDescription()->getHtml();
        $bookInfo->cover = (string) $book->getImageUrl();
        if (!empty($book->getSeriesHeader())) {
            $matches = '';
            if (preg_match('/([\d\.-]+)/', $book->getSeriesHeader(), $matches)) {
                // @todo convert to float before importing in Calibre
                //$bookInfo->serieIndex = str_replace(['-', '·'], ['.', '.'], $matches[1]);
                $bookInfo->serieIndex = $matches[1];
            }
        }
        // set in loadSeries()
        //$bookInfo->serie = $seriesResult->getTitle();
        //$bookInfo->serieIds = [ $seriesResult->getId() ];
        $bookInfo->creationDate = (string) empty($book->getPublicationDate()) ? '2000-01-01 00:00:00' : $book->getPublicationDate();
        // @todo no modification date here
        $bookInfo->modificationDate = $bookInfo->creationDate;
        // Timestamp is used to get latest ebooks
        $bookInfo->timeStamp = BookInfo::getSqlDate($bookInfo->creationDate);
        $bookInfo->rating = $book->getAvgRating();
        $bookInfo->identifiers = ['goodreads' => $bookInfo->name];

        return $bookInfo;
    }

    /**
     * Summary of getBookInfo
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfo|SeriesResult|SearchResult
     */
    public static function getBookInfo($dbPath, $data)
    {
        if (!empty($data["page"]) && $data["page"] == "/book/show/[book_id]") {
            $book = GoodReadsCache::parseBook($data);
            return self::load($dbPath, $book);
        }
        // don't load all books in search result here
        if (array_key_first($data) == 0) {
            return GoodReadsCache::parseSeries($data);
        }
        return GoodReadsCache::parseSearch($data);
    }
}
