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
        $bookInfo->source = 'openlibrary';
        $bookInfo->basePath = $basePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfo->format = 'epub';
        $bookInfo->id = (string) str_replace('/works/', '', $work->getKey());
        $bookInfo->uri = (string) OpenLibraryMatch::link($bookInfo->id);
        // @todo use calibre_external_storage in COPS
        $bookInfo->path = $bookInfo->uri;
        if (str_starts_with($bookInfo->path, $basePath)) {
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
            $author = $cache->loadCache($cacheFile);
            if (empty($author['name'])) {
                continue;
            }
            $authorSort = AuthorInfo::getNameSort($author['name']);
            if (!empty($author['bio']) && is_array($author['bio'])) {
                $description = $author['bio']['value'] ?? '';
            } else {
                $description = $author['bio'] ?? '';
            }
            $info = [
                'id' => $authorId,
                'name' => $author['name'],
                'sort' => $authorSort,
                'link' => OpenLibraryMatch::link($authorId),
                'description' => $description,
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
            sort($covers, SORT_NUMERIC);
            $cover = reset($covers);
            // @see https://openlibrary.org/dev/docs/api/covers
            $bookInfo->cover = "https://covers.openlibrary.org/b/id/{$cover}-M.jpg";
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
