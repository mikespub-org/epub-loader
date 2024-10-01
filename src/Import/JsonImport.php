<?php
/**
 * CsvImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\BookInfos;
use Marsender\EPubLoader\Metadata\GoodReads\BookShowResult;
use Marsender\EPubLoader\Metadata\GoogleBooks\SearchResult;
use Marsender\EPubLoader\Metadata\GoogleBooks\Volume;
use Marsender\EPubLoader\RequestHandler;
use Exception;

class JsonImport extends BookImport
{
    /**
     * Load books from JSON file
     * @param string $inBasePath base directory
     * @param string $fileName
     * @return array{string, array<mixed>}
     */
    public function loadFromJsonFile($inBasePath, $fileName)
    {
        $content = file_get_contents($fileName);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $errors = [];
        $nbOk = 0;
        $nbError = 0;
        if (!empty($data["kind"]) && $data["kind"] == "books#volumes") {
            $result = SearchResult::fromJson($data);
            foreach ($result->getItems() as $volume) {
                try {
                    // Load the book infos
                    $bookInfos = self::loadFromGoogleBooksVolume($inBasePath, $volume);
                    // Add the book
                    $this->addBook($bookInfos, 0);
                    $nbOk++;
                } catch (Exception $e) {
                    $id = $volume->getId() ?? spl_object_hash($volume);
                    $errors[$id] = $e->getMessage();
                    $nbError++;
                }
            }
        } elseif (!empty($data["kind"]) && $data["kind"] == "books#volume") {
            $volume = Volume::fromJson($data);
            try {
                // Load the book infos
                $bookInfos = self::loadFromGoogleBooksVolume($inBasePath, $volume);
                // Add the book
                $this->addBook($bookInfos, 0);
                $nbOk++;
            } catch (Exception $e) {
                $id = $volume->getId() ?? spl_object_hash($volume);
                $errors[$id] = $e->getMessage();
                $nbError++;
            }
        } elseif (!empty($data["page"]) && $data["page"] == "/book/show/[book_id]") {
            $book = BookShowResult::fromJson($data);
            try {
                // Load the book infos
                $bookInfos = self::loadFromGoodReadsBook($inBasePath, $book);
                // Add the book
                $this->addBook($bookInfos, 0);
                $nbOk++;
            } catch (Exception $e) {
                $id = basename($fileName) ?? spl_object_hash($book);
                $errors[$id] = $e->getMessage();
                $nbError++;
            }
        } else {
            // @todo add more formats to support
        }
        $message = sprintf('Import ebooks from %s - %d files OK - %d files Error', $fileName, $nbOk, $nbError);
        return [$message, $errors];
    }

    /**
     * Loads book infos from an Google Books volume
     *
     * @param string $inBasePath base directory
     * @param Volume $volume Google Books volume
     * @throws Exception if error
     *
     * @return BookInfos
     */
    public static function loadFromGoogleBooksVolume($inBasePath, $volume)
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
            }
        }
        $bookInfos->mCreationDate = (string) $volumeInfo->getPublishedDate();
        // @todo no modification date here
        $bookInfos->mModificationDate = $bookInfos->mCreationDate;
        // Timestamp is used to get latest ebooks
        $bookInfos->mTimeStamp = $bookInfos->mCreationDate;

        return $bookInfos;
    }

    /**
     * Loads book infos from an GoodReads book
     *
     * @param string $inBasePath base directory
     * @param BookShowResult $book GoodReads book
     * @throws Exception if error
     *
     * @return BookInfos
     */
    public static function loadFromGoodReadsBook($inBasePath, $book)
    {
        $state = $book->getProps()->getPageProps()->getApolloState();
        if (empty($state)) {
            throw new Exception('Invalid format for GoodReads book');
        }
        $bookRef = $state->getRootQuery()->getGetBookByLegacyId()->getRef();
        $bookMap = $state->getBookMap();
        if (empty($bookRef) || empty($bookMap) || empty($bookMap[$bookRef])) {
            throw new Exception('Invalid format for GoodReads book');
        }
        $workRef = $bookMap[$bookRef]->getWork()->getRef();
        $workMap = $state->getWorkMap();
        if (empty($workRef) || empty($workMap) || empty($workMap[$workRef])) {
            throw new Exception('Invalid format for GoodReads book');
        }

        $bookInfos = new BookInfos();
        // @todo ...

        return $bookInfos;
    }

    /**
     * Load books from JSON files in path
     *
     * @param string $inBasePath base directory
     * @param string $jsonPath relative to $inBasePath
     *
     * @return array{string, array<mixed>}
     */
    public function loadFromPath($inBasePath, $jsonPath)
    {
        $allErrors = [];
        $allMessages = '';
        $fileList = RequestHandler::getFiles($inBasePath . DIRECTORY_SEPARATOR . $jsonPath, '*.json');
        foreach ($fileList as $file) {
            [$message, $errors] = $this->loadFromJsonFile($inBasePath, $file);
            //$allMessages .= $message . '<br />';
            //$allErrors = array_merge($allErrors, $errors);
            $allMessages = $message;
            $allErrors = $errors;
        }
        return [$allMessages, $allErrors];
    }
}
