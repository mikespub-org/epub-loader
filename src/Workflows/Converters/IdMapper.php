<?php
/**
 * IdMapper class
 */

namespace Marsender\EPubLoader\Workflows\Converters;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;
use Exception;

/**
 * Map bookId to filename
 */
class IdMapper extends Converter
{
    /** @var array<mixed> */
    protected $bookId = null;
    protected string $bookIdFileName = '';

    /**
     * Load mapper file if available
     *
     * @param string $bookIdsFileName File name containing a map of file names to calibre book ids
     * @throws Exception if error
     */
    public function __construct($bookIdsFileName = '')
    {
        if (!empty($bookIdsFileName)) {
            $this->loadBookIds($bookIdsFileName);
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->saveBookIds();
    }

    /**
     * Load the book ids map in order to reuse calibe book id when recreating database
     *
     * @param string $bookIdsFileName File name containing a map of file names to calibre book ids
     *
     * @return void
     */
    protected function loadBookIds($bookIdsFileName)
    {
        $this->bookId = [];
        $this->bookIdFileName = $bookIdsFileName;

        if (empty($this->bookIdFileName) || !file_exists($this->bookIdFileName)) {
            return;
        }

        // Load the book ids file
        $lines = file($this->bookIdFileName);
        foreach ($lines as $line) {
            $tab = explode("\t", trim($line));
            if (count($tab) != 2) {
                continue;
            }
            $this->bookId[$tab[0]] = (int) $tab[1];
        }
    }

    /**
     * Save the book ids file
     * @return void
     */
    protected function saveBookIds()
    {
        if (empty($this->bookIdFileName)) {
            return;
        }

        $tab = [];
        foreach ($this->bookId as $key => $value) {
            $tab[] = sprintf('%s%s%d', $key, "\t", $value);
        }

        file_put_contents($this->bookIdFileName, implode("\n", $tab) . "\n");
    }

    /**
     * Get id from name
     * @param string $type only 'books' supported for now
     * @param string $name
     * @return int id
     */
    public function getId($type, $name)
    {
        if (isset($this->bookId[$name])) {
            return (int) $this->bookId[$name];
        }
        // Get max book id
        $res = 0;
        foreach ($this->bookId as $key => $value) {
            if ($value > $res) {
                $res = $value;
            }
        }
        $res++;
        $this->bookId[$name] = $res;

        return $res;
    }

    /**
     * Convert info and/or id
     *
     * @param BookInfo|AuthorInfo|SeriesInfo $info object
     * @param int $id id in the calibre db (or 0 for auto incrementation)
     * @return array{0: BookInfo|AuthorInfo|SeriesInfo, 1: int}
     */
    public function convert($info, $id = 0)
    {
        if (empty($id) && $info instanceof BookInfo) {
            // @see LocalBooksImport::load()
            $fileName = $info->path . DIRECTORY_SEPARATOR . $info->id;
            $id = $this->getId('books', $fileName);
        }
        return [$info, $id];
    }
}
