<?php
/**
 * CallbackWriter class
 */

namespace Marsender\EPubLoader\Workflows\Writers;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;

class CallbackWriter extends TargetWriter
{
    /** @var array<string, callable> */
    protected $callbacks = [];
    protected int $nbBook = 0;
    protected int $nbAuthor = 0;
    protected int $nbSeries = 0;

    /**
     * Set book, author & series info via callback function
     *
     * @param array<mixed> $callbacks
     */
    public function __construct($callbacks = [])
    {
        $this->callbacks = $callbacks;
    }

    /**
     * Set book info via callback
     *
     * @param BookInfo $bookInfo BookInfo object
     * @param int $bookId Book id in the calibre db
     * @return void
     */
    public function addBook($bookInfo, $bookId = 0)
    {
        $this->nbBook++;
        //$bookId = $bookId ?: $this->nbBook;
        $callback = $this->callbacks['setBookInfo'] ?? '';
        if (empty($bookId) || empty($callback)) {
            return;
        }
        $callback($bookId, $bookInfo);
    }

    /**
     * Set author info via callback
     *
     * @param AuthorInfo $authorInfo
     * @param mixed $authorId Author id in the calibre db
     * @return void
     */
    public function addAuthor($authorInfo, $authorId = 0)
    {
        $this->nbAuthor++;
        //$authorId = $authorId ?: $this->nbAuthor;
        $callback = $this->callbacks['setAuthorInfo'] ?? '';
        if (empty($authorId) || empty($callback)) {
            return;
        }
        $callback($authorId, $authorInfo);
    }

    /**
     * Set series info via callback
     *
     * @param SeriesInfo $seriesInfo
     * @param mixed $seriesId Series id in the calibre db
     * @return void
     */
    public function addSeries($seriesInfo, $seriesId = 0)
    {
        $this->nbSeries++;
        //$seriesId = $seriesId ?: $this->nbSeries;
        $callback = $this->callbacks['setSeriesInfo'] ?? '';
        if (empty($seriesId) || empty($callback)) {
            return;
        }
        $callback($seriesId, $seriesInfo);
    }
}
