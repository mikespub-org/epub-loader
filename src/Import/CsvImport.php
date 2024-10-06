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
use Exception;

class CsvImport extends SourceImport
{
    public const CSV_SEPARATOR = "\t";
    public const CSV_ENCLOSURE = "'";

    /**
     * Load books from CSV export/import file
     * @param string $inBasePath base directory
     * @param string $fileName
     * @return array{string, array<mixed>}
     */
    public function loadFromPath($inBasePath, $fileName)
    {
        $handle = fopen($fileName, 'r');
        $headers = fgetcsv($handle, null, self::CSV_SEPARATOR, self::CSV_ENCLOSURE);
        $errors = [];
        $nbOk = 0;
        $nbError = 0;
        while (($data = fgetcsv($handle, null, self::CSV_SEPARATOR, self::CSV_ENCLOSURE)) !== false) {
            try {
                // Load the book infos
                $bookInfos = self::loadFromArray($inBasePath, $data);
                // Add the book
                $this->addBook($bookInfos, 0);
                $nbOk++;
            } catch (Exception $e) {
                $errors[$data[1]] = $e->getMessage();
                $nbError++;
            }
        }
        $message = sprintf('Import ebooks from %s - %d files OK - %d files Error', $fileName, $nbOk, $nbError);
        return [$message, $errors];
    }

    /**
     * Loads book infos from an export/import array
     * @see \Marsender\EPubLoader\Export\ExportCsvFile::addBook()
     *
     * @param string $inBasePath base directory
     * @param array<mixed> $inArray CSV import info (one book per line)
     * @throws Exception if error
     *
     * @return BookInfos
     */
    public static function loadFromArray($inBasePath, $inArray)
    {
        if (empty($inArray) || count($inArray) < 20) {
            throw new Exception('Invalid format for CSV book array: ' . count($inArray) . ' fields');
        }
        $bookInfos = new BookInfos();
        // Get the epub infos from array - see BookExport::addBook()
        $bookInfos->mBasePath = $inBasePath;
        $i = 0;
        $bookInfos->mFormat = $inArray[$i++];
        $bookInfos->mPath = $inArray[$i++];
        if (str_starts_with($bookInfos->mPath, $inBasePath)) {
            $bookInfos->mPath = substr($bookInfos->mPath, strlen($inBasePath) + 1);
        }
        $bookInfos->mName = $inArray[$i++];
        $bookInfos->mUuid = $inArray[$i++];
        $bookInfos->mUri = $inArray[$i++];
        $bookInfos->mTitle = $inArray[$i++];
        $values = explode(' - ', $inArray[$i++]);
        $keys = explode(' - ', $inArray[$i++]);
        $bookInfos->mAuthors = array_combine($keys, $values);
        $bookInfos->mLanguage = $inArray[$i++];
        $bookInfos->mDescription = $inArray[$i++];
        $bookInfos->mSubjects = explode(' - ', $inArray[$i++]);
        $bookInfos->mCover = $inArray[$i++];
        $bookInfos->mIsbn = $inArray[$i++];
        $bookInfos->mRights = $inArray[$i++];
        $bookInfos->mPublisher = $inArray[$i++];
        $bookInfos->mSerie = $inArray[$i++];
        $bookInfos->mSerieIndex = $inArray[$i++];
        $bookInfos->mCreationDate = $inArray[$i++] ?? '';
        $bookInfos->mModificationDate = $inArray[$i++] ?? '';
        // Timestamp is used to get latest ebooks
        $bookInfos->mTimeStamp = $bookInfos->mCreationDate;

        return $bookInfos;
    }
}
