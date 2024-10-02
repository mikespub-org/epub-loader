<?php
/**
 * GoodReadsMatch class - @todo
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata\Sources;

use Exception;

class GoodReadsMatch extends BaseMatch
{
    public const ENTITY_URL = 'https://www.goodreads.com/book/show/';
    public const ENTITY_PATTERN = '/^\d+(|\.\w+)$/';
    public const CACHE_TYPES = ['goodreads/book/show', 'goodreads/author/list', 'goodreads/series', 'goodreads/search'];
    public const QUERY_URL = 'https://www.goodreads.com/search?q={query}&search_type=books';  // &utf8=%E2%9C%93&tab=books&per_page={limit}

    /**
     * Summary of getBook
     * @param string $bookId
     * @param string|null $lang Language (default: en)
     * @return array<string, mixed>
     */
    public function getBook($bookId, $lang = null)
    {
        if (str_contains($bookId, '.')) {
            [$bookId, $title] = explode('.', $bookId);
        }
        $lang ??= $this->lang;
        if ($this->cacheDir) {
            $cacheFile = $this->cacheDir . '/goodreads/book/show/' . $bookId . '.json';
            if (is_file($cacheFile)) {
                return $this->loadCache($cacheFile);
            }
        }
        $url = static::link($bookId);
        $result = file_get_contents($url);
        $result = $this->parseBookPage($bookId, $result);
        $entity = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        if ($this->cacheDir) {
            $this->saveCache($cacheFile, $entity);
        }
        return $entity;
    }

    /**
     * Parse html page and get JSON string
     * @param string $bookId
     * @param string $content
     * @return string
     */
    public function parseBookPage($bookId, $content)
    {
        $matches = [];
        if (!preg_match('~<script id="__NEXT_DATA__" type="application/json">(.+?)</script>~', $content, $matches)) {
            if ($this->cacheDir) {
                $cacheFile = $this->cacheDir . '/goodreads/book/show/' . $bookId . '.htm';
                file_put_contents($cacheFile, $content);
            }
            throw new Exception('Unable to find JSON data in html page: see ' . $bookId . '.htm');
        }
        return $matches[1];
    }
}
