<?php
/**
 * GoogleBooksImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

use Marsender\EPubLoader\Metadata\AuthorInfo;
use Marsender\EPubLoader\Metadata\BookInfo;
use Marsender\EPubLoader\Metadata\GoogleBooks\Volumes\Volume;
use Exception;

class GoogleBooksImport
{
    /**
     * Loads book infos from a Google Books volume
     *
     * @param string $basePath base directory
     * @param Volume $volume Google Books volume
     * @throws Exception if error
     *
     * @return BookInfo
     */
    public static function load($basePath, $volume)
    {
        $volumeInfo = $volume->getVolumeInfo();
        if (empty($volumeInfo)) {
            throw new Exception('Invalid format for Google Books Volume');
        }

        $bookInfo = new BookInfo();
        $bookInfo->source = 'google';
        $bookInfo->basePath = $basePath;
        // @todo check accessInfo for epub, pdf etc.
        $bookInfo->format = 'epub';
        // @todo use calibre_external_storage in COPS
        $bookInfo->path = (string) $volume->getSelfLink();
        if (str_starts_with($bookInfo->path, $basePath)) {
            $bookInfo->path = substr($bookInfo->path, strlen($basePath) + 1);
        }
        $bookInfo->name = (string) $volume->getId();
        $bookInfo->uuid = 'google:' . $bookInfo->name;
        $bookInfo->uri = (string) ($volumeInfo->getCanonicalVolumeLink() ?? $volume->getSelfLink());
        $bookInfo->title = (string) $volumeInfo->getTitle();
        $authors = [];
        foreach ($volumeInfo->getAuthors() as $author) {
            $authorSort = AuthorInfo::getNameSort($author);
            $authors[$authorSort] = $author;
        }
        $bookInfo->authors = $authors;
        $bookInfo->authorIds = $volumeInfo->getAuthors();
        $bookInfo->language = (string) $volumeInfo->getLanguage();
        $bookInfo->description = (string) $volumeInfo->getDescription();
        $bookInfo->subjects = $volumeInfo->getCategories();
        $bookInfo->cover = (string) $volumeInfo->getImageLinks()?->getThumbnail();
        $identifiers = $volumeInfo->getIndustryIdentifiers();
        if (!empty($identifiers)) {
            foreach ($identifiers as $identifier) {
                if ($identifier->getType() == 'ISBN_13') {
                    $bookInfo->isbn = $identifier->getIdentifier();
                    break;
                }
                if ($identifier->getType() == 'ISBN_10') {
                    $bookInfo->isbn = $identifier->getIdentifier();
                    break;
                }
            }
        }
        //$bookInfo->rights = $array[$i++];
        $bookInfo->publisher = (string) $volumeInfo->getPublisher();
        $series = $volumeInfo->getSeriesInfo();
        if (!empty($series)) {
            $bookInfo->serieIndex = (string) $series->getBookDisplayNumber();
            // @todo use title to get series name
            if (str_contains($bookInfo->title, ':')) {
                [$seriesName, $title] = explode(':', $bookInfo->title, 2);
                $seriesName = preg_replace('/\s*Vol.\s*/', '', preg_replace('/\s*\d+\s*/', '', $seriesName));
                $bookInfo->serie = trim($seriesName);
            } elseif (!empty($series->getVolumeSeries())) {
                $info = $series->getVolumeSeries()[0];
                // @todo get series name from id
                $bookInfo->serie = (string) $info->getSeriesId();
                $bookInfo->serieIds = [ (string) $info->getSeriesId() ];
            }
        }
        $bookInfo->creationDate = (string) $volumeInfo->getPublishedDate();
        // @todo no modification date here
        $bookInfo->modificationDate = $bookInfo->creationDate;
        // Timestamp is used to get latest ebooks
        $bookInfo->timeStamp = $bookInfo->creationDate;
        $bookInfo->rating = $volumeInfo->getAverageRating();
        $bookInfo->identifiers = ['google' => $bookInfo->name];
        if (!empty($bookInfo->isbn)) {
            $bookInfo->identifiers['isbn'] = $bookInfo->isbn;
        }

        return $bookInfo;
    }

    /**
     * Summary of getBookInfo
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfo|array<BookInfo>
     */
    public static function getBookInfo($dbPath, $data)
    {
        if (!empty($data["kind"]) && $data["kind"] == "books#volume") {
            $volume = GoogleBooksCache::parseVolume($data);
            return self::load($dbPath, $volume);
        }
        // load all volumes in search result
        $result = GoogleBooksCache::parseSearch($data);
        if (empty($result->getItems())) {
            $result->items = [];
        }
        $books = [];
        foreach ($result->getItems() as $volume) {
            $books[] = self::load($dbPath, $volume);
        }
        return $books;
    }
}
