<?php
/**
 * OpenLibraryImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\OpenLibrary;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Metadata\OpenLibrary\Entities\WorkEntity;
use Exception;

class OpenLibraryImport
{
    public const SOURCE = 'openlibrary';

    /**
     * Load book info from an OpenLibrary work
     *
     * @param string $basePath base directory
     * @param WorkEntity $work OpenLibrary work
     * @param OpenLibraryCache|null $cache
     * @throws Exception if error
     *
     * @return BookInfo
     */
    public static function load($basePath, $work, $cache = null)
    {
        if (empty($cache)) {
            if (basename($basePath) == 'openlibrary') {
                $cacheDir = dirname($basePath);
            } else {
                $cacheDir = dirname(__DIR__, 3) . '/cache';
            }
            $cache = new OpenLibraryCache($cacheDir);
        }

        $bookInfo = new BookInfo();
        $bookInfo->source = self::SOURCE;
        $bookInfo->basePath = $basePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfo->format = 'epub';
        $bookInfo->id = (string) str_replace('/works/', '', $work->getKey());
        $bookInfo->uri = (string) OpenLibraryMatch::link($bookInfo->id);
        // @todo use calibre_external_storage in COPS
        $bookInfo->path = $bookInfo->uri;
        if (!empty($basePath) && str_starts_with($bookInfo->path, $basePath)) {
            $bookInfo->path = substr($bookInfo->path, strlen($basePath) + 1);
        }
        $bookInfo->uuid = 'olid:' . $bookInfo->id;
        $bookInfo->title = (string) $work->getTitle();
        if (is_array($work->getDescription())) {
            $bookInfo->description = (string) ($work->getDescription()['value'] ?? '');
        } else {
            $bookInfo->description = (string) $work->getDescription();
        }
        $entities = $work->getAuthors() ?? [];
        foreach ($entities as $author) {
            $authorId = (string) $author->getAuthor()?->getKey();
            if (empty($authorId)) {
                continue;
            }
            $authorId = str_replace('/authors/', '', $authorId);
            // lookup author info here
            $cacheFile = $cache->getAuthor($authorId);
            if (!$cache->hasCache($cacheFile)) {
                continue;
            }
            $authorData = $cache->loadCache($cacheFile);
            if (empty($authorData['name'])) {
                continue;
            }
            $authorEntity = OpenLibraryCache::parseAuthorEntity($authorData);
            $authorSort = AuthorInfo::getNameSort($authorEntity->getName());
            $bio = $authorEntity->getBio();
            if (!empty($bio) && is_array($bio)) {
                $description = $bio['value'] ?? '';
            } else {
                $description = $bio ?? '';
            }
            $covers = $authorEntity->getPhotos();
            if (!empty($covers) && is_array($covers)) {
                // pick the lowest cover number?
                //sort($covers, SORT_NUMERIC);
                $cover = reset($covers);
                // @see https://openlibrary.org/dev/docs/api/covers
                $image = "https://covers.openlibrary.org/a/id/{$cover}-M.jpg";
            } else {
                $image = "https://covers.openlibrary.org/a/olid/{$authorId}-M.jpg";
            }
            $info = [
                'id' => $authorId,
                'name' => $authorEntity->getName(),
                'sort' => $authorSort,
                'link' => OpenLibraryMatch::link($authorId),
                'image' => $image,
                'description' => $description,
                'source' => self::SOURCE,
            ];
            $bookInfo->addAuthor($authorId, $info);
        }
        $subjects = [];
        $entities = $work->getSubjects() ?? [];
        foreach ($entities as $subject) {
            $subjects[] = (string) $subject;
        }
        $bookInfo->subjects = $subjects;
        if (!empty($work->getCovers())) {
            $covers = $work->getCovers();
            // pick the lowest cover number?
            //sort($covers, SORT_NUMERIC);
            $cover = reset($covers);
            // @see https://openlibrary.org/dev/docs/api/covers
            $bookInfo->cover = "https://covers.openlibrary.org/b/id/{$cover}-M.jpg";
        } else {
            // @todo we need an edition OL...M olid for this to work
            //$workId = $bookInfo->id;
            //$bookInfo->cover = "https://covers.openlibrary.org/b/olid/{$workid}-M.jpg";
        }
        // @todo ...
        //$bookInfo->addSeries(0, '...');

        $bookInfo->creationDate = (string) $work->getCreated()?->getValue();
        // @todo no modification date here
        $bookInfo->modificationDate = (string) ($work->getLastModified()?->getValue() ?? $bookInfo->creationDate);
        // Timestamp is used to get latest ebooks
        $bookInfo->timestamp = $bookInfo->creationDate;
        $bookInfo->identifiers = ['olid' => $bookInfo->id];
        if (!empty($bookInfo->isbn)) {
            $bookInfo->identifiers['isbn'] = $bookInfo->isbn;
        }

        return $bookInfo;
    }

    /**
     * Summary of getBookInfo
     * @param string $dbPath
     * @param array<mixed> $data
     * @return BookInfo
     */
    public static function getBookInfo($dbPath, $data)
    {
        $work = OpenLibraryCache::parseWorkEntity($data);
        return self::load($dbPath, $work);
    }
}
