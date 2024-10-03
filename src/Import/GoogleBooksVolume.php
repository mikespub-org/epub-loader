<?php
/**
 * GoogleBooksVolume import class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\BookInfos;
use Marsender\EPubLoader\Metadata\GoogleBooks\SearchResult;
use Marsender\EPubLoader\Metadata\GoogleBooks\Volume;
use Exception;

class GoogleBooksVolume
{
    /**
     * Parse JSON data for Google Books search result
     *
     * @param array<mixed> $data
     *
     * @return SearchResult
     */
    public static function parseResult($data)
    {
        $result = SearchResult::fromJson($data);
        return $result;
    }

    /**
     * Parse JSON data for a Google Books volume
     *
     * @param array<mixed> $data
     *
     * @return Volume
     */
    public static function parse($data)
    {
        $volume = Volume::fromJson($data);
        return $volume;
    }

    /**
     * Loads book infos from a Google Books volume
     *
     * @param string $inBasePath base directory
     * @param Volume $volume Google Books volume
     * @throws Exception if error
     *
     * @return BookInfos
     */
    public static function load($inBasePath, $volume)
    {
        $volumeInfo = $volume->getVolumeInfo();
        if (empty($volumeInfo)) {
            throw new Exception('Invalid format for Google Books Volume');
        }

        $bookInfos = new BookInfos();
        $bookInfos->mBasePath = $inBasePath;
        // @todo check accessInfo for epub, pdf etc.
        $bookInfos->mFormat = 'epub';
        // @todo use calibre_external_storage in COPS
        $bookInfos->mPath = (string) $volume->getSelfLink();
        if (str_starts_with($bookInfos->mPath, $inBasePath)) {
            $bookInfos->mPath = substr($bookInfos->mPath, strlen($inBasePath) + 1);
        }
        $bookInfos->mName = (string) $volume->getId();
        $bookInfos->mUuid = 'google:' . $bookInfos->mName;
        $bookInfos->mUri = (string) ($volumeInfo->getCanonicalVolumeLink() ?? $volume->getSelfLink());
        $bookInfos->mTitle = (string) $volumeInfo->getTitle();
        $authors = [];
        foreach ($volumeInfo->getAuthors() as $author) {
            $authorSort = BookInfos::getSortString($author);
            $authors[$authorSort] = $author;
        }
        $bookInfos->mAuthors = $authors;
        $bookInfos->mLanguage = (string) $volumeInfo->getLanguage();
        $bookInfos->mDescription = (string) $volumeInfo->getDescription();
        $bookInfos->mSubjects = $volumeInfo->getCategories();
        $bookInfos->mCover = (string) $volumeInfo->getImageLinks()?->getThumbnail();
        $identifiers = $volumeInfo->getIndustryIdentifiers();
        if (!empty($identifiers)) {
            foreach ($identifiers as $identifier) {
                if ($identifier->getType() == 'ISBN_13') {
                    $bookInfos->mIsbn = $identifier->getIdentifier();
                    break;
                }
                if ($identifier->getType() == 'ISBN_10') {
                    $bookInfos->mIsbn = $identifier->getIdentifier();
                    break;
                }
            }
        }
        //$bookInfos->mRights = $inArray[$i++];
        $bookInfos->mPublisher = (string) $volumeInfo->getPublisher();
        $series = $volumeInfo->getSeriesInfo();
        if (!empty($series)) {
            $bookInfos->mSerieIndex = (string) $series->getBookDisplayNumber();
            // @todo use title to get series name
            if (str_contains($bookInfos->mTitle, ':')) {
                [$seriesName, $title] = explode(':', $bookInfos->mTitle, 2);
                $seriesName = preg_replace('/\s*Vol.\s*/', '', preg_replace('/\s*\d+\s*/', '', $seriesName));
                $bookInfos->mSerie = trim($seriesName);
            } elseif (!empty($series->getVolumeSeries())) {
                $info = $series->getVolumeSeries()[0];
                // @todo get series name from id
                $bookInfos->mSerie = (string) $info->getSeriesId();
                $bookInfos->mSerieId = (string) $info->getSeriesId();
            }
        }
        $bookInfos->mCreationDate = (string) $volumeInfo->getPublishedDate();
        // @todo no modification date here
        $bookInfos->mModificationDate = $bookInfos->mCreationDate;
        // Timestamp is used to get latest ebooks
        $bookInfos->mTimeStamp = $bookInfos->mCreationDate;
        $bookInfos->mRating = $volumeInfo->getAverageRating();
        $bookInfos->mIdentifiers = ['google' => $bookInfos->mName];

        return $bookInfos;
    }

    /**
     * Summary of import
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfos|array<BookInfos>
     */
    public static function import($dbPath, $data)
    {
        if (!empty($data["kind"]) && $data["kind"] == "books#volume") {
            $volume = static::parse($data);
            return static::load($dbPath, $volume);
        }
        $result = static::parseResult($data);
        if (empty($result->getItems())) {
            $result->items = [];
        }
        $books = [];
        foreach ($result->getItems() as $volume) {
            $books[] = static::load($dbPath, $volume);
        }
        return $books;
    }
}
