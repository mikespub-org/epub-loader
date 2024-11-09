<?php
/**
 * GoogleBooksImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\GoogleBooks;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Metadata\GoogleBooks\Volumes\Volume;
use Exception;
use Marsender\EPubLoader\Models\SeriesInfo;

class GoogleBooksImport
{
    public const SOURCE = 'Google Books';

    /**
     * Load book info from a Google Books volume
     *
     * @param string $basePath base directory
     * @param Volume $volume Google Books volume
     * @param GoogleBooksCache|null $cache
     * @throws Exception if error
     *
     * @return BookInfo
     */
    public static function load($basePath, $volume, $cache = null)
    {
        $volumeInfo = $volume->getVolumeInfo();
        if (empty($volumeInfo)) {
            throw new Exception('Invalid format for Google Books Volume');
        }

        $bookInfo = new BookInfo();
        $bookInfo->source = self::SOURCE;
        $bookInfo->basePath = $basePath;
        // @todo check accessInfo for epub, pdf etc.
        $bookInfo->format = 'epub';
        $bookInfo->id = (string) $volume->getId();
        $bookInfo->uri = (string) ($volumeInfo->getCanonicalVolumeLink() ?? $volume->getSelfLink());
        // @todo use calibre_external_storage in COPS
        $bookInfo->path = $bookInfo->uri;
        if (!empty($basePath) && str_starts_with($bookInfo->path, $basePath)) {
            $bookInfo->path = substr($bookInfo->path, strlen($basePath) + 1);
        }
        $bookInfo->uuid = 'google:' . $bookInfo->id;
        $bookInfo->title = (string) $volumeInfo->getTitle();
        foreach ($volumeInfo->getAuthors() as $authorName) {
            $authorName = self::fixAuthorName($authorName);
            $authorSort = AuthorInfo::getNameSort($authorName);
            $authorId = $authorName;
            $info = [
                'id' => '',
                'name' => $authorName,
                'sort' => $authorSort,
                'link' => '',
                'image' => '',
                'description' => '',
                'source' => self::SOURCE,
            ];
            $bookInfo->addAuthor($authorId, $info);
        }
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
            // @todo use title to get series name
            if (str_contains($bookInfo->title, ':')) {
                [$seriesTitle, $title] = explode(':', $bookInfo->title, 2);
            } else {
                $seriesTitle = $bookInfo->title;
            }
            $seriesTitle = preg_replace('/\s*Vol.\s*/i', '', (string) preg_replace('/\s*\d+\s*/', '', $seriesTitle));
            $seriesSort = SeriesInfo::getTitleSort($seriesTitle);
            $seriesList = $series->getVolumeSeries() ?? [];
            if (empty($seriesList)) {
                $seriesList[] = Volumes\VolumeSeries::fromJson(['seriesId' => $seriesTitle]);
            }
            $index = (string) $series->getBookDisplayNumber();
            foreach ($seriesList as $volumeSeries) {
                // @todo get series name from id
                $seriesId = (string) $volumeSeries->getSeriesId();
                if (!empty($bookInfo->series)) {
                    $index = (string) $volumeSeries->getOrderNumber();
                }
                $info = [
                    'id' => $seriesId,
                    'name' => $seriesTitle,
                    'sort' => $seriesSort,
                    'index' => $index,
                    'image' => '',
                    'description' => '',
                    'source' => self::SOURCE,
                ];
                $bookInfo->addSeries($seriesId, $info);
            }
        }
        $bookInfo->creationDate = (string) $volumeInfo->getPublishedDate();
        // @todo no modification date here
        $bookInfo->modificationDate = $bookInfo->creationDate;
        // Timestamp is used to get latest ebooks
        $bookInfo->timestamp = $bookInfo->creationDate;
        $bookInfo->rating = $volumeInfo->getAverageRating();
        $bookInfo->count = $volumeInfo->getRatingsCount();
        $bookInfo->identifiers = ['google' => $bookInfo->id];
        if (!empty($bookInfo->isbn)) {
            $bookInfo->identifiers['isbn'] = $bookInfo->isbn;
        }

        return $bookInfo;
    }

    /**
     * Summary of fixAuthorName
     * @param string $authorName
     * @return string
     */
    public static function fixAuthorName($authorName)
    {
        if (str_contains($authorName, '(')) {
            [$first, $last] = explode('(', $authorName, 2);
            $authorName = trim($first);
        }
        if (str_contains($authorName, ',')) {
            [$last, $first] = explode(',', $authorName, 2);
            $authorName = trim($first) . ' ' . trim($last);
        }
        $title = explode(' ', $authorName)[0];
        if (in_array(strtolower($title), ['sir', 'dr.'])) {
            $authorName = trim(substr($authorName, strlen($title)));
        }
        return $authorName;
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
