<?php
/**
 * GoodReadsImport class
 */

namespace Marsender\EPubLoader\Metadata\GoodReads;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Marsender\EPubLoader\Metadata\GoodReads\Books\BookResult;
use Marsender\EPubLoader\Metadata\GoodReads\Search\AuthorEntry;
use Marsender\EPubLoader\Metadata\GoodReads\Search\SearchResult;
use Marsender\EPubLoader\Metadata\GoodReads\Series\SeriesResult;
use Marsender\EPubLoader\Metadata\GoodReads\Series\Book as SeriesBook;
use Exception;

class GoodReadsImport
{
    public const SOURCE = 'GoodReads';

    /**
     * Load book info from a GoodReads book
     *
     * @param string $basePath base directory
     * @param BookResult $bookResult GoodReads book show
     * @param GoodReadsCache|null $cache
     * @throws Exception if error
     *
     * @return BookInfo
     */
    public static function load($basePath, $bookResult, $cache = null)
    {
        if (empty($cache)) {
            if (basename($basePath) == 'goodreads') {
                $cacheDir = dirname($basePath);
            } else {
                $cacheDir = dirname(__DIR__, 3) . '/cache';
            }
            $cache = new GoodReadsCache($cacheDir);
        }

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
        $bookInfo->source = self::SOURCE;
        $bookInfo->basePath = $basePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfo->format = 'epub';
        $bookInfo->id = (string) $book->getLegacyId();
        $bookInfo->uri = (string) $book->getWebUrl();
        // @todo use calibre_external_storage in COPS
        $bookInfo->path = $bookInfo->uri;
        if (!empty($basePath) && str_starts_with($bookInfo->path, $basePath)) {
            $bookInfo->path = substr($bookInfo->path, strlen($basePath) + 1);
        }
        if (!empty($bookInfo->id)) {
            $bookInfo->uuid = 'goodreads:' . $bookInfo->id;
        } else {
            $bookInfo->createUuid();
            $bookInfo->id = $bookInfo->uuid;
        }
        $bookInfo->title = (string) $book->getTitle();
        $authorRef = $book->getPrimaryContributorEdge()?->getNode()?->getRef();
        $contributors = $state->getContributorMap();
        if (empty($authorRef) || empty($contributors) || empty($contributors[$authorRef])) {
            throw new Exception('Invalid authorRef for GoodReads book');
        }
        $authorName = (string) $contributors[$authorRef]->getName();
        $authorSort = AuthorInfo::getNameSort($authorName);
        $authorId = str_replace('https://www.goodreads.com/author/show/', '', (string) $contributors[$authorRef]->getWebUrl());
        if (empty($authorId)) {
            $authorId = $authorName;
            $link = '';
        } else {
            $link = GoodReadsMatch::AUTHOR_URL . $authorId;
        }
        // author image may be available for primary contributor
        $image = (string) $contributors[$authorRef]->getProfileImageUrl();
        // author description may be available for primary contributor
        $description = (string) $contributors[$authorRef]->getDescription();
        $info = [
            'id' => $authorId,
            'name' => $authorName,
            'sort' => $authorSort,
            'link' => $link,
            'image' => $image,
            'description' => $description,
            'source' => self::SOURCE,
        ];
        $bookInfo->addAuthor($authorId, $info);
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
            $authorName = (string) $contributors[$authorRef]->getName();
            $authorSort = AuthorInfo::getNameSort($authorName);
            $authorId = str_replace('https://www.goodreads.com/author/show/', '', (string) $contributors[$authorRef]->getWebUrl());
            if (empty($authorId)) {
                $authorId = $authorName;
                $link = '';
            } else {
                $link = GoodReadsMatch::AUTHOR_URL . $authorId;
            }
            // @todo this is empty for secondary contributors
            $image = (string) $contributors[$authorRef]->getProfileImageUrl();
            $description = (string) $contributors[$authorRef]->getDescription();
            $info = [
                'id' => $authorId,
                'name' => $authorName,
                'sort' => $authorSort,
                'link' => $link,
                'image' => $image,
                'description' => $description,
                'source' => self::SOURCE,
            ];
            $bookInfo->addAuthor($authorId, $info);
        }
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
        foreach ($bookSeries as $series) {
            $seriesRef = $series->getSeries()?->getRef();
            if (empty($seriesRef) || empty($seriesMap) || empty($seriesMap[$seriesRef])) {
                throw new Exception('Invalid seriesRef for GoodReads book');
            }
            // use only the 1st series for name & index here
            $index = (string) $series->getUserPosition();
            $title = (string) $seriesMap[$seriesRef]->getTitle();
            // save ids of the other series here for matching?
            $seriesId = str_replace('https://www.goodreads.com/series/', '', (string) $seriesMap[$seriesRef]->getWebUrl());
            // series description is available from cached series
            $description = '';
            $cacheFile = $cache->getSeries($seriesId);
            if ($cache->hasCache($cacheFile)) {
                $seriesData = $cache->loadCache($cacheFile);
                $seriesResult = GoodReadsCache::parseSeries($seriesData);
                $description = $seriesResult->getDescription() ?? '';
            }
            $link = (string) $seriesMap[$seriesRef]->getWebUrl();
            if (empty($link)) {
                $link = GoodReadsMatch::SERIES_URL . $seriesId;
            }
            // series image is not available
            $image = '';
            $info = [
                'id' => $seriesId,
                'name' => $title,
                'sort' => SeriesInfo::getTitleSort($title),
                'link' => $link,
                'image' => $image,
                'description' => $description,
                'source' => self::SOURCE,
            ];
            if (empty($bookInfo->series)) {
                $info['index'] = $index;
            }
            $bookInfo->addSeries($seriesId, $info);
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
        $bookInfo->timestamp = $bookInfo->creationDate;
        $bookInfo->rating = $work->getStats()?->getAverageRating();
        $bookInfo->count = $work->getStats()?->getRatingsCount();
        $bookInfo->identifiers = ['goodreads' => $bookInfo->id];
        if (!empty($bookInfo->isbn)) {
            $bookInfo->identifiers['isbn'] = $bookInfo->isbn;
        }
        $bookInfo->properties = [];
        $consumed = [];
        $book->getWork()->ref = null;
        $book->legacyId = null;
        $book->webUrl = null;
        $book->title = null;
        $book->primaryContributorEdge = null;
        $book->secondaryContributorEdges = null;
        $book->getDetails()->getLanguage()->name = null;
        $book->getDetails()->getLanguage()->typename = null;
        $book->description = null;
        $book->bookGenres = null;
        $book->imageUrl = null;
        $book->getDetails()->isbn13 = null;
        $book->getDetails()->publisher = null;
        foreach ($book->bookSeries as $idx => $series) {
            $series->getSeries()->ref = null;
            $series->userPosition = null;
            $series->typename = null;
            $book->bookSeries[$idx] = $series;
        }
        $book->getDetails()->publicationTime = null;
        $book->getDetails()->typename = null;
        $book = self::filterProperties($book) ?? [];
        $bookInfo->properties['book'] = array_filter($book, function ($key) use ($consumed) {
            return !in_array($key, $consumed);
        }, ARRAY_FILTER_USE_KEY);
        $consumed = [];
        $work->getDetails()->publicationTime = null;
        $work->getStats()->averageRating = null;
        $work->getStats()->ratingsCount = null;
        $work = self::filterProperties($work) ?? [];
        $bookInfo->properties['work'] = array_filter($work, function ($key) use ($consumed) {
            return !in_array($key, $consumed);
        }, ARRAY_FILTER_USE_KEY);
        foreach ($contributors as $idx => $contributor) {
            $contributor->id = null;
            $contributor->name = null;
            $contributor->webUrl = null;
            $contributor->profileImageUrl = null;
            $contributor->description = null;
            $contributor->typename = null;
            if ($contributor->getWorks()) {
                $contributor->getWorks()->typename = null;
            }
            if ($contributor->getFollowers()) {
                $contributor->getFollowers()->typename = null;
            }
            $contributors[$idx] = $contributor;
        }
        $contributors = self::filterProperties($contributors) ?? [];
        $bookInfo->properties['contributors'] = array_filter($contributors, function ($key) use ($consumed) {
            return !in_array($key, $consumed);
        }, ARRAY_FILTER_USE_KEY);
        foreach ($seriesMap as $idx => $series) {
            $series->id = null;
            $series->title = null;
            $series->webUrl = null;
            $series->typename = null;
            $seriesMap[$idx] = $series;
        }
        $seriesMap = self::filterProperties($seriesMap) ?? [];
        $bookInfo->properties['seriesMap'] = array_filter($seriesMap, function ($key) use ($consumed) {
            return !in_array($key, $consumed);
        }, ARRAY_FILTER_USE_KEY);

        return $bookInfo;
    }

    public static function filterProperties($object)
    {
        if (is_object($object)) {
            $object = (array) $object;
        }
        $result = [];
        foreach ($object as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $value = self::filterProperties($value);
            }
            if (!is_null($value)) {
                $result[$key] = $value;
            }
        }
        if (empty($result)) {
            return null;
        }
        return $result;
    }

    /**
     * Load author & book info from a GoodReads search
     *
     * @param string $basePath base directory
     * @param SearchResult $searchResult GoodReads search
     * @throws Exception if error
     *
     * @return array<mixed>
     */
    public static function loadSearch($basePath, $searchResult)
    {
        $entries = $searchResult->getProperties();
        // <a href="{{endpoint}}/{{action}}/{{dbNum}}/{{cacheName}}/{{cacheType}}?entry={{entry}}">{{entry}}</a>
        foreach ($entries as $authorId => $authorEntry) {
            $authorEntry = self::loadSearchAuthor($basePath, $authorEntry);
            $entries[$authorId] = $authorEntry;
        }
        return $entries;
    }

    /**
     * Load author & book info from a GoodReads search entry
     *
     * @param string $basePath base directory
     * @param AuthorEntry $authorEntry GoodReads search entry
     * @throws Exception if error
     *
     * @return AuthorInfo
     */
    public static function loadSearchAuthor($basePath, $authorEntry)
    {
        $authorInfo = new AuthorInfo();
        $authorInfo->source = self::SOURCE;
        $authorInfo->basePath = $basePath;
        $authorInfo->id = (string) $authorEntry->getId();
        $authorInfo->name = (string) $authorEntry->getName();
        $authorInfo->sort = AuthorInfo::getNameSort($authorInfo->name);
        $authorInfo->link = GoodReadsMatch::AUTHOR_URL . $authorInfo->id;
        //$authorInfo->addNote($data['description']);
        //$authorInfo->image = $data['image'] ?? '';
        foreach ($authorEntry->getBooks() as $id => $book) {
            $bookId = (string) $book->getId();
            $entryId = GoodReadsMatch::bookid($bookId);
            $title = (string) $book->getTitle();
            $cover = (string) $book->getCover();
            $description = '';
            $rating = (float) $book->getRating();
            $count = (int) $book->getCount();
            $info = [
                'id' => $bookId,
                'title' => $title,
                'sort' => BookInfo::getTitleSort($title),
                'cover' => $cover,
                'uri' => GoodReadsMatch::link($bookId),
                'description' => $description,
                'rating' => $rating,
                'count' => $count,
                'author' => $authorInfo->id,
                //'series' => $seriesInfo->id,
                'source' => self::SOURCE,
            ];
            $authorInfo->addBook($bookId, $info);
        }
        //$authorInfo->addSeries($seriesId, $info);
        $consumed = ['id', 'name', 'books'];
        $authorInfo->properties = array_filter((array) $authorEntry, function ($key) use ($consumed) {
            return !in_array($key, $consumed);
        }, ARRAY_FILTER_USE_KEY);

        return $authorInfo;
    }

    /**
     * Load book info from a GoodReads series
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
        // info for series in BookInfo - @todo do we want that?
        $seriesId = $seriesResult->getId();
        $title = $seriesResult->getTitle();
        $image = '';
        $description = $seriesResult->getDescription();
        $seriesInfo = [
            'id' => $seriesId,
            'name' => $title,
            'sort' => SeriesInfo::getTitleSort($title),
            'link' => GoodReadsMatch::SERIES_URL . $seriesId,
            'image' => $image,
            'description' => $description,
            'source' => self::SOURCE,
        ];
        foreach ($seriesResult->getBookList() as $key => $book) {
            if (empty($book->getBookId())) {
                continue;
            }
            // convert to BookInfo
            $bookInfo = self::loadSeriesBook($basePath, $book, $seriesInfo);
            $series['bookList'][$key] = $bookInfo;
        }
        return $series;
    }

    /**
     * Load book info from a GoodReads series book
     *
     * @param string $basePath base directory
     * @param SeriesBook $book GoodReads series book
     * @param array<mixed> $seriesInfo info for series
     * @throws Exception if error
     *
     * @return BookInfo
     */
    public static function loadSeriesBook($basePath, $book, $seriesInfo)
    {
        $bookInfo = new BookInfo();
        $bookInfo->source = self::SOURCE;
        $bookInfo->basePath = $basePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfo->format = 'epub';
        $bookInfo->id = (string) $book->getBookId();
        $bookInfo->uri = (string) $book->getBookUrl();
        if (str_starts_with($bookInfo->uri, '/book/show/')) {
            $bookInfo->uri = 'https://www.goodreads.com' . $bookInfo->uri;
        }
        // @todo use calibre_external_storage in COPS
        $bookInfo->path = $bookInfo->uri;
        if (!empty($basePath) && str_starts_with($bookInfo->path, $basePath)) {
            $bookInfo->path = substr($bookInfo->path, strlen($basePath) + 1);
        }
        if (!empty($bookInfo->id)) {
            $bookInfo->uuid = 'goodreads:' . $bookInfo->id;
        } else {
            $bookInfo->createUuid();
            $bookInfo->id = $bookInfo->uuid;
        }
        $bookInfo->title = (string) ($book->getBookTitleBare() ?? $book->getTitle());
        $author = $book->getAuthor();
        if (!empty($author) && !empty($author->getId())) {
            $authorId = $author->getId();
            $authorName = $author->getName();
            $authorSort = AuthorInfo::getNameSort($authorName);
            if (str_starts_with((string) $author->getWorksListUrl(), GoodReadsMatch::AUTHOR_URL)) {
                $authorId = str_replace(GoodReadsMatch::AUTHOR_URL, '', $author->getWorksListUrl());
            }
            // @todo author description is available from cached book contributors
            $image = '';
            $description = '';
            $info = [
                'id' => $authorId,
                'name' => $authorName,
                'sort' => $authorSort,
                'link' => $author->getWorksListUrl(),
                'image' => $image,
                'description' => $description,
                'source' => self::SOURCE,
            ];
            $bookInfo->addAuthor($authorId, $info);
        }
        $bookInfo->description = (string) $book->getDescription()->getHtml();
        $bookInfo->cover = (string) $book->getImageUrl();
        if (!empty($book->getSeriesHeader())) {
            $matches = '';
            if (preg_match('/([\d\.-]+)/', $book->getSeriesHeader(), $matches)) {
                // @todo convert to float before importing in Calibre
                //$seriesInfo['index'] = str_replace(['-', '·'], ['.', '.'], $matches[1]);
                $seriesInfo['index'] = $matches[1];
            }
        }
        $bookInfo->addSeries(0, $seriesInfo);
        $bookInfo->creationDate = (string) empty($book->getPublicationDate()) ? '2000-01-01 00:00:00' : $book->getPublicationDate();
        // @todo no modification date here
        $bookInfo->modificationDate = $bookInfo->creationDate;
        // Timestamp is used to get latest ebooks
        $bookInfo->timestamp = BookInfo::getSqlDate($bookInfo->creationDate);
        $bookInfo->rating = $book->getAvgRating();
        $bookInfo->count = $book->getRatingsCount();
        $bookInfo->identifiers = ['goodreads' => $bookInfo->id];
        $consumed = ['bookId', 'bookUrl', 'bookTitleBare', 'description', 'imageUrl', 'seriesHeader', 'publicationDate', 'avgRating', 'ratingsCount'];
        $bookInfo->properties = array_filter((array) $book, function ($key) use ($consumed) {
            return !in_array($key, $consumed);
        }, ARRAY_FILTER_USE_KEY);
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
