<?php
/**
 * LocalBooksImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\LocalBooks;

use Marsender\EPubLoader\Metadata\BookEPub;
use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Exception;

class LocalBooksImport
{
    /**
     * Load book info from an epub file
     *
     * @param string $basePath Epub base directory
     * @param string $fileName Epub file name (from base directory)
     * @param mixed $cache
     * @throws Exception if error
     *
     * @return BookInfo
     */
    public static function load($basePath, $fileName, $cache = null)
    {
        $fullFileName = sprintf('%s%s%s', $basePath, DIRECTORY_SEPARATOR, $fileName);
        // Check file access
        if (!is_readable($fullFileName)) {
            throw new Exception('Cannot read file');
        }

        // Load the epub file
        $ePub = new BookEPub($fullFileName);

        // Check epub version
        $version = $ePub->getEpubVersion();
        switch ($version) {
            case 2:
            case 3:
                break;
            default:
                $error = sprintf('Incorrect ebook epub version=%d', $version);
                throw new Exception($error);
        }

        // Load the book infos
        $bookInfo = new BookInfo();

        $bookInfo->source = 'local';
        $bookInfo->basePath = $basePath;
        $bookInfo->format = 'epub';
        $bookInfo->path = pathinfo($fileName, PATHINFO_DIRNAME);
        $bookInfo->id = pathinfo($fileName, PATHINFO_FILENAME);
        $bookInfo->uuid = $ePub->getUniqueIdentifier() ?: $ePub->getUuid();
        $bookInfo->uri = $ePub->getUri();
        $bookInfo->title = $ePub->getTitle();
        $authors = $ePub->getAuthors();
        foreach ($authors as $authorSort => $authorName) {
            $authorId = $authorName;
            $info = [
                'id' => '',
                'name' => $authorSort,
                'sort' => $authorName,
                'link' => '',
                'description' => '',
            ];
            $bookInfo->addAuthor($authorId, $info);
        }
        $bookInfo->language = $ePub->getLanguage();
        $bookInfo->description = $ePub->getDescription();
        $bookInfo->subjects = $ePub->getSubjects();
        $cover = $ePub->getCoverInfo();
        $cover = $cover['found'];
        if (($cover !== false)) {
            // Remove meta base path
            $meta = $ePub->meta();
            $len = strlen($meta) - strlen(pathinfo($meta, PATHINFO_BASENAME));
            $bookInfo->cover = substr((string) $cover, $len);
        }
        $bookInfo->isbn = $ePub->getIsbn();
        $bookInfo->rights = $ePub->getCopyright();
        $bookInfo->publisher = $ePub->getPublisher();
        if ($version == 3) {
            // Note: this will ignore collections without id, e.g. in epub-tests files:
            // <meta property="belongs-to-collection">should</meta>
            [$title, $index] = $ePub->getSeriesOrCollection();
        } else {
            // Tag sample in opf file:
            //   <meta content="Histoire de la Monarchie de Juillet" name="calibre:series"/>
            $title = $ePub->getSeries();
            // Tag sample in opf file:
            //   <meta content="7" name="calibre:series_index"/>
            $index = $ePub->getSeriesIndex();
        }
        if (!empty($title)) {
            $info = [
                'id' => '',
                'name' => $title,
                'sort' => SeriesInfo::getTitleSort($title),
                'index' => $index,
                'link' => '',
                'description' => '',
            ];
            $bookInfo->addSeries(0, $info);
        }
        $bookInfo->creationDate = BookInfo::getSqlDate($ePub->getCreationDate()) ?? '';
        $bookInfo->modificationDate = BookInfo::getSqlDate($ePub->getModificationDate()) ?? '';
        // Timestamp is used to get latest ebooks
        $bookInfo->timestamp = $bookInfo->creationDate;
        if (!empty($bookInfo->isbn)) {
            $bookInfo->identifiers['isbn'] = $bookInfo->isbn;
        }

        return $bookInfo;
    }
}
