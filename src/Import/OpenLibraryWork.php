<?php
/**
 * OpenLibraryWork import class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\BookInfos;
use Marsender\EPubLoader\Metadata\OpenLibrary\WorkEntity;
use Exception;
use Marsender\EPubLoader\Metadata\Sources\OpenLibraryMatch;

class OpenLibraryWork
{
    /**
     * Parse JSON data for an OpenLibrary work
     *
     * @param array<mixed> $data
     *
     * @return WorkEntity
     */
    public static function parse($data)
    {
        $work = WorkEntity::fromJson($data);
        return $work;
    }

    /**
     * Loads book infos from an OpenLibrary work
     *
     * @param string $inBasePath base directory
     * @param WorkEntity $work OpenLibrary work
     * @throws Exception if error
     *
     * @return BookInfos
     */
    public static function load($inBasePath, $work)
    {
        // @todo get from somewhere
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new OpenLibraryMatch($cacheDir);

        $bookInfos = new BookInfos();
        $bookInfos->mBasePath = $inBasePath;
        // @todo check details format and/or links for epub, pdf etc.
        $bookInfos->mFormat = 'epub';
        $bookInfos->mName = (string) str_replace('/works/', '', $work->getKey());
        // @todo use calibre_external_storage in COPS
        $bookInfos->mPath = (string) OpenLibraryMatch::link($bookInfos->mName);
        if (str_starts_with($bookInfos->mPath, $inBasePath)) {
            $bookInfos->mPath = substr($bookInfos->mPath, strlen($inBasePath) + 1);
        }
        $bookInfos->mUuid = 'olid:' . $bookInfos->mName;
        $bookInfos->mUri = (string) $bookInfos->mPath;
        $bookInfos->mTitle = (string) $work->getTitle();
        if (is_array($work->getDescription())) {
            $bookInfos->mDescription = (string) $work->getDescription()['value'] ?? '';
        } else {
            $bookInfos->mDescription = (string) $work->getDescription();
        }
        $authors = [];
        $entities = $work->getAuthors() ?? [];
        foreach ($entities as $author) {
            $authorId = (string) $author->getAuthor()?->getKey();
            if (empty($authorId)) {
                continue;
            }
            $authorId = str_replace('/authors/', '', $authorId);
            // lookup author info here
            $author = $match->getAuthor($authorId);
            if (empty($author['name'])) {
                continue;
            }
            $authorSort = BookInfos::getSortString($author['name']);
            $authors[$authorSort] = $author['name'];
        }
        $bookInfos->mAuthors = $authors;
        $subjects = [];
        $entities = $work->getSubjects() ?? [];
        foreach ($entities as $subject) {
            $subjects[] = (string) $subject;
        }
        $bookInfos->mSubjects = $subjects;
        if (!empty($work->getCovers())) {
            $covers = $work->getCovers();
            // pick the lowest cover number?
            sort($covers, SORT_NUMERIC);
            $cover = reset($covers);
            // @see https://openlibrary.org/dev/docs/api/covers
            $bookInfos->mCover = "https://covers.openlibrary.org/b/id/{$cover}-M.jpg";
        }
        // @todo ...

        $bookInfos->mCreationDate = (string) $work->getCreated()?->getValue();
        // @todo no modification date here
        $bookInfos->mModificationDate = (string) $work->getLastModified()?->getValue() ?? $bookInfos->mCreationDate;
        // Timestamp is used to get latest ebooks
        $bookInfos->mTimeStamp = $bookInfos->mCreationDate;
        $bookInfos->mIdentifiers = ['olid' => $bookInfos->mName];

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
        $work = static::parse($data);
        return static::load($dbPath, $work);
    }
}
