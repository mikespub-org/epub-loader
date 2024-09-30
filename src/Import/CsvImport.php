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

class CsvImport extends BookImport
{
    public const CSV_SEPARATOR = "\t";
    public const CSV_ENCLOSURE = "'";

    /**
     * Summary of loadFromCsvFile
     * @param string $dbPath
     * @param string $fileName
     * @return array{string, array<mixed>}
     */
    public function loadFromCsvFile($dbPath, $fileName)
    {
        $handle = fopen($fileName, 'r');
        $headers = fgetcsv($handle, null, self::CSV_SEPARATOR, self::CSV_ENCLOSURE);
        $errors = [];
        $nbOk = 0;
        $nbError = 0;
        while (($data = fgetcsv($handle, null, self::CSV_SEPARATOR, self::CSV_ENCLOSURE)) !== false) {
            // Load the book infos
            $bookInfos = new BookInfos();
            $bookInfos->loadFromArray($dbPath, $data);
            try {
                $this->addBook($bookInfos, 0);
            } catch (Exception $e) {
                $errors[$bookInfos->mPath] = $e->getMessage();
                $nbError++;
                continue;
            }
            $nbOk++;
        }
        $message = sprintf('Import ebooks from %s - %d files OK - %d files Error', $fileName, $nbOk, $nbError);
        return [$message, $errors];
    }
}
