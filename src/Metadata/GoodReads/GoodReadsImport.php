<?php
/**
 * GoodReadsImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\GoodReads;

use Marsender\EPubLoader\Metadata\BookInfos;
use Marsender\EPubLoader\Metadata\GoodReads\Books\BookResult;
use Marsender\EPubLoader\Metadata\GoodReads\Search\SearchResult;
use Marsender\EPubLoader\Metadata\GoodReads\Series\SeriesResult;
use Exception;

class GoodReadsImport
{
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
        $authorId = str_replace('https://www.goodreads.com/author/show/', '', (string) $contributors[$authorRef]->getWebUrl());
        $bookInfos->mAuthorIds = [];
        if (!empty($authorId)) {
            $bookInfos->mAuthorIds[] = $authorId;
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
            $authorSort = BookInfos::getSortString($author);
            $authors[$authorSort] = $author;
            $authorId = str_replace('https://www.goodreads.com/author/show/', '', (string) $contributors[$authorRef]->getWebUrl());
            if (!empty($authorId)) {
                $bookInfos->mAuthorIds[] = $authorId;
            }
        }
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
        $seriesMap = $state->getSeriesMap();
        $bookSeries = $book->getBookSeries() ?? [];
        $bookInfos->mSerieIds = [];
        foreach ($bookSeries as $series) {
            $seriesRef = $series->getSeries()?->getRef();
            if (empty($seriesRef) || empty($seriesMap) || empty($seriesMap[$seriesRef])) {
                throw new Exception('Invalid seriesRef for GoodReads book');
            }
            // use only the 1st series for name & index here
            if ($bookInfos->mSerieIndex == '') {
                $bookInfos->mSerieIndex = (string) $series->getUserPosition();
            }
            if ($bookInfos->mSerie == '') {
                $bookInfos->mSerie = (string) $seriesMap[$seriesRef]->getTitle();
            }
            // save ids of the other series here for matching?
            $bookInfos->mSerieIds[] = str_replace('https://www.goodreads.com/series/', '', (string) $seriesMap[$seriesRef]->getWebUrl());
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
     * Summary of getBookInfos
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfos|SeriesResult|SearchResult
     */
    public static function getBookInfos($dbPath, $data)
    {
        if (!empty($data["page"]) && $data["page"] == "/book/show/[book_id]") {
            $book = GoodReadsCache::parseBook($data);
            return static::load($dbPath, $book);
        }
        // don't load all books in search result here
        if (array_keys($data)[0] == 0) {
            return GoodReadsCache::parseSeries($data);
        }
        return GoodReadsCache::parseSearch($data);
    }
}
