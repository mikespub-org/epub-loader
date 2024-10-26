<?php
/**
 * CsvImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Models\BookInfo;
use Exception;
use Marsender\EPubLoader\Models\SeriesInfo;

class CsvImport extends SourceImport
{
    public const CSV_SEPARATOR = "\t";
    public const CSV_ENCLOSURE = "'";

    /**
     * Load books from CSV export/import file
     * @param string $basePath base directory
     * @param string $fileName
     * @return array{string, array<mixed>}
     */
    public function loadFromPath($basePath, $fileName)
    {
        $handle = fopen($fileName, 'r');
        $headers = fgetcsv($handle, null, self::CSV_SEPARATOR, self::CSV_ENCLOSURE);
        $errors = [];
        $nbOk = 0;
        $nbError = 0;
        while (($data = fgetcsv($handle, null, self::CSV_SEPARATOR, self::CSV_ENCLOSURE)) !== false) {
            try {
                // Load the book infos
                $bookInfo = self::loadFromArray($basePath, $data);
                // Add the book
                $this->addBook($bookInfo, 0);
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
     * Load book info from an export/import array
     * @see \Marsender\EPubLoader\Export\ExportCsvFile::addBook()
     *
     * @param string $basePath base directory
     * @param array<mixed> $array CSV import info (one book per line)
     * @throws Exception if error
     *
     * @return BookInfo
     */
    public static function loadFromArray($basePath, $array)
    {
        if (empty($array) || count($array) < 20) {
            throw new Exception('Invalid format for CSV book array: ' . count($array) . ' fields');
        }
        $bookInfo = new BookInfo();
        // Get the epub infos from array - see BookExport::addBook()
        $bookInfo->basePath = $basePath;
        $i = 0;
        $bookInfo->format = $array[$i++];
        $bookInfo->path = $array[$i++];
        if (str_starts_with($bookInfo->path, $basePath)) {
            $bookInfo->path = substr($bookInfo->path, strlen($basePath) + 1);
        }
        $bookInfo->id = $array[$i++];
        $bookInfo->uuid = $array[$i++];
        $bookInfo->uri = $array[$i++];
        $bookInfo->title = $array[$i++];
        $values = explode(' - ', (string) $array[$i++]);
        $keys = explode(' - ', (string) $array[$i++]);
        $authors = array_combine($keys, $values);
        foreach ($authors as $authorSort => $authorName) {
            $info = [
                'id' => '',
                'name' => $authorName,
                'sort' => $authorSort,
            ];
            $bookInfo->addAuthor($authorName, $info);
        }
        $bookInfo->language = $array[$i++];
        $bookInfo->description = $array[$i++];
        $bookInfo->subjects = explode(' - ', (string) $array[$i++]);
        $bookInfo->cover = $array[$i++];
        $bookInfo->isbn = $array[$i++];
        $bookInfo->rights = $array[$i++];
        $bookInfo->publisher = $array[$i++];
        //$bookInfo->serie = $array[$i++];
        //$bookInfo->serieIndex = $array[$i++];
        $title = $array[$i++];
        $index = $array[$i++];
        $info = [
            'id' => '',
            'name' => $title,
            'sort' => SeriesInfo::getTitleSort($title),
            'index' => $index,
        ];
        $bookInfo->addSeries(0, $info);
        $bookInfo->creationDate = $array[$i++] ?? '';
        $bookInfo->modificationDate = $array[$i++] ?? '';
        // Timestamp is used to get latest ebooks
        $bookInfo->timestamp = $bookInfo->creationDate;

        return $bookInfo;
    }
}
