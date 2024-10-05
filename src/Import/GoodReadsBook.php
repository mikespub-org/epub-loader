<?php
/**
 * GoodReadsBook import class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\BookInfos;
use Marsender\EPubLoader\Metadata\GoodReads\Books\BookResult;
use Marsender\EPubLoader\Metadata\GoodReads\Search\SearchResult;
use Marsender\EPubLoader\Metadata\GoodReads\Series\SeriesResult;
use Exception;

class GoodReadsBook
{
    /**
     * Parse JSON data for GoodReads search result
     *
     * @param array<mixed> $data
     *
     * @return SearchResult
     */
    public static function parseSearch($data)
    {
        $result = SearchResult::fromJson($data);
        return $result;
    }

    /**
     * Parse JSON data for GoodReads series result
     *
     * @param array<mixed> $data
     *
     * @return SeriesResult
     */
    public static function parseSeries($data)
    {
        $result = SeriesResult::fromJson($data);
        return $result;
    }

    /**
     * Parse JSON data for a GoodReads book
     *
     * @param array<mixed> $data
     *
     * @return BookResult
     */
    public static function parse($data)
    {
        $bookResult = BookResult::fromJson($data);
        return $bookResult;
    }

    /**
     * Loads book infos from a GoodReads book
     *
     * @param string $inBasePath base directory
     * @param BookResult $bookResult GoodReads book show
     * @throws Exception if error
     *
     * @return BookInfos
     */
    public static function load($inBasePath, $bookResult)
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

        $bookInfos = new BookInfos();
        $bookInfos->mBasePath = $inBasePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfos->mFormat = 'epub';
        // @todo use calibre_external_storage in COPS
        $bookInfos->mPath = (string) $book->getWebUrl();
        if (str_starts_with($bookInfos->mPath, $inBasePath)) {
            $bookInfos->mPath = substr($bookInfos->mPath, strlen($inBasePath) + 1);
        }
        $bookInfos->mName = (string) $book->getLegacyId();
        if (!empty($bookInfos->mName)) {
            $bookInfos->mUuid = 'goodreads:' . $bookInfos->mName;
        } else {
            $bookInfos->createUuid();
            $bookInfos->mName = $bookInfos->mUuid;
        }
        $bookInfos->mUri = (string) $book->getWebUrl();
        $bookInfos->mTitle = (string) $book->getTitle();
        $authors = [];
        $authorRef = $book->getPrimaryContributorEdge()?->getNode()?->getRef();
        $contributors = $state->getContributorMap();
        if (empty($authorRef) || empty($contributors) || empty($contributors[$authorRef])) {
            throw new Exception('Invalid authorRef for GoodReads book');
        }
        $author = (string) $contributors[$authorRef]->getName();
        $authorSort = BookInfos::getSortString($author);
        $authors[$authorSort] = $author;
        // @todo add authors from secondaryContributorEdges?
        $bookInfos->mAuthors = $authors;
        $bookInfos->mLanguage = (string) $book->getDetails()?->getLanguage()?->getName();
        $bookInfos->mDescription = (string) $book->getDescription();
        $subjects = [];
        $bookGenres = $book->getBookGenres() ?? [];
        foreach ($bookGenres as $bookGenre) {
            $subject = $bookGenre->getGenre()?->getName();
            if (empty($subject)) {
                continue;
            }
            $subjects[] = (string) $subject;
        }
        $bookInfos->mSubjects = $subjects;
        $bookInfos->mCover = (string) $book->getImageUrl();
        $isbn = $book->getDetails()?->getIsbn13();
        if (empty($isbn)) {
            $isbn = $book->getDetails()?->getIsbn();
        }
        if (!empty($isbn)) {
            $bookInfos->mIsbn = (string) $isbn;
        }
        //$bookInfos->mRights = $inArray[$i++];
        $bookInfos->mPublisher = (string) $book->getDetails()?->getPublisher();
        $bookSeries = $book->getBookSeries() ?? [];
        foreach ($bookSeries as $series) {
            $bookInfos->mSerieIndex = (string) $series->getUserPosition();
            $seriesRef = $series->getSeries()?->getRef();
            $seriesMap = $state->getSeriesMap();
            if (empty($seriesRef) || empty($seriesMap) || empty($seriesMap[$seriesRef])) {
                throw new Exception('Invalid seriesRef for GoodReads book');
            }
            $bookInfos->mSerie = (string) $seriesMap[$seriesRef]->getTitle();
            $bookInfos->mSerieId = str_replace('https://www.goodreads.com/series/', '', (string) $seriesMap[$seriesRef]->getWebUrl());
            break;
        }
        // timestamp in milliseconds since the epoch for Javascript
        $timestamp = $book->getDetails()?->getPublicationTime() ?? $work->getDetails()?->getPublicationTime();
        if (!empty($timestamp)) {
            // format as '@timestamp' in seconds since the epoch for DateTime()
            $timestamp = '@' . (string) intval($timestamp / 1000);
        }
        $bookInfos->mCreationDate = (string) BookInfos::getSqlDate($timestamp);
        // @todo no modification date here
        $bookInfos->mModificationDate = $bookInfos->mCreationDate;
        // Timestamp is used to get latest ebooks
        $bookInfos->mTimeStamp = $bookInfos->mCreationDate;
        $bookInfos->mRating = $work->getStats()?->getAverageRating();
        $bookInfos->mIdentifiers = ['goodreads' => $bookInfos->mName];

        return $bookInfos;
    }

    /**
     * Summary of import
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfos
     */
    public static function import($dbPath, $data)
    {
        $book = static::parse($data);
        return static::load($dbPath, $book);
    }
}
