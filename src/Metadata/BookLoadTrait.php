<?php
/**
 * BookLoadTrait for use in BookExport and BookImport
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata;

use Marsender\EPubLoader\Import\BaseImport;
use Marsender\EPubLoader\Metadata\BookEPub;
use Marsender\EPubLoader\Metadata\BookInfos;
use Exception;

trait BookLoadTrait
{
    /**
     * Load books from epub files in path
     *
     * @param string $inBasePath base directory
     * @param string $epubPath relative to $inBasePath
     *
     * @return array{string, array<mixed>}
     */
    public function loadFromPath($inBasePath, $epubPath)
    {
        $errors = [];
        $nbOk = 0;
        $nbError = 0;
        if (!empty($epubPath)) {
            $fileList = BaseImport::getFiles($inBasePath . DIRECTORY_SEPARATOR . $epubPath, '*.epub');
            foreach ($fileList as $file) {
                $filePath = substr($file, strlen((string) $inBasePath) + 1);
                try {
                    // Load the book infos
                    $bookInfos = static::loadFromEpub($inBasePath, $filePath);
                    // Add the book
                    $bookId = $this->getBookId($filePath);
                    $this->addBook($bookInfos, $bookId);
                    $nbOk++;
                } catch (Exception $e) {
                    $errors[$file] = $e->getMessage();
                    $nbError++;
                }
            }
        }
        $message = sprintf('%s %s - %d files OK - %d files Error', $this->mLabel, $this->mFileName, $nbOk, $nbError);
        return [$message, $errors];
    }

    /**
     * Loads book infos from an epub file
     *
     * @param string $inBasePath Epub base directory
     * @param string $inFileName Epub file name (from base directory)
     * @throws Exception if error
     *
     * @return BookInfos
     */
    public static function loadFromEpub($inBasePath, $inFileName)
    {
        $fullFileName = sprintf('%s%s%s', $inBasePath, DIRECTORY_SEPARATOR, $inFileName);
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
        $bookInfos = new BookInfos();

        $bookInfos->mBasePath = $inBasePath;
        $bookInfos->mFormat = 'epub';
        $bookInfos->mPath = pathinfo($inFileName, PATHINFO_DIRNAME);
        $bookInfos->mName = pathinfo($inFileName, PATHINFO_FILENAME);
        $bookInfos->mUuid = $ePub->getUniqueIdentifier() ?: $ePub->getUuid();
        $bookInfos->mUri = $ePub->getUri();
        $bookInfos->mTitle = $ePub->getTitle();
        $bookInfos->mAuthors = $ePub->getAuthors();
        $bookInfos->mLanguage = $ePub->getLanguage();
        $bookInfos->mDescription = $ePub->getDescription();
        $bookInfos->mSubjects = $ePub->getSubjects();
        $cover = $ePub->getCoverInfo();
        $cover = $cover['found'];
        if (($cover !== false)) {
            // Remove meta base path
            $meta = $ePub->meta();
            $len = strlen($meta) - strlen(pathinfo($meta, PATHINFO_BASENAME));
            $bookInfos->mCover = substr((string) $cover, $len);
        }
        $bookInfos->mIsbn = $ePub->getIsbn();
        $bookInfos->mRights = $ePub->getCopyright();
        $bookInfos->mPublisher = $ePub->getPublisher();
        // Tag sample in opf file:
        //   <meta content="Histoire de la Monarchie de Juillet" name="calibre:series"/>
        $bookInfos->mSerie = $ePub->getSeries();
        // Tag sample in opf file:
        //   <meta content="7" name="calibre:series_index"/>
        $bookInfos->mSerieIndex = $ePub->getSeriesIndex();
        $bookInfos->mCreationDate = BookInfos::getSqlDate($ePub->getCreationDate()) ?? '';
        $bookInfos->mModificationDate = BookInfos::getSqlDate($ePub->getModificationDate()) ?? '';
        // Timestamp is used to get latest ebooks
        $bookInfos->mTimeStamp = $bookInfos->mCreationDate;

        return $bookInfos;
    }
}
