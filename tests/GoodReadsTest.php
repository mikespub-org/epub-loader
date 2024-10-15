<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\CalibreDbLoader;
use Marsender\EPubLoader\Import\JsonImport;
use Marsender\EPubLoader\Metadata\BookInfos;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsCache;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsImport;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsMatch;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Exception;

class GoodReadsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!file_exists(dirname(__DIR__) . '/app/config.php')) {
            copy(dirname(__DIR__) . '/app/config.php.example', dirname(__DIR__) . '/app/config.php');
        }
        $_SERVER['SCRIPT_NAME'] = '/phpunit';
    }

    /**
     * Summary of testAppGetAuthors
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetAuthors(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_author/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_author/0">Some Books</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppGetAuthorLinks
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetAuthorLinks(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_author/0';
        $_GET['findLinks'] = '1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_author/0/4?matchId=880695.H_G_Wells">880695.H_G_Wells</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'H. G. Wells';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['findLinks']);
    }

    /**
     * Summary of testAppGetAuthor
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetAuthor(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_author/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_books/0/1?authId=2448.Arthur_Conan_Doyle">2448.Arthur_Conan_Doyle</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Arthur Conan Doyle';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppGetBooks
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetBooks(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_books/0/1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_books/0/1?bookId=11">Search</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'A Study in Scarlet';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppGetBookSearch
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetBookSearch(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_books/0/1';
        $_GET['bookId'] = '11';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_books/0/1?bookId=11&matchId=102868">102868</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'A Study in Scarlet (Sherlock Holmes, #1)';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['bookId']);
    }

    /**
     * Summary of testAppGetSeries
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetSeries(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_series/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_series/0/1?seriesId=1">Search</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Sherlock Holmes';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppGetSeriesMatch
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetSeriesMatch(): void
    {
        $_SERVER['PATH_INFO'] = '/gr_series/0';
        $_GET['matchId'] = '49996-sherlock-holmes';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/gr_series/0/?matchId=49996-sherlock-holmes">49996-sherlock-holmes</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Sherlock Holmes is a fictional consulting detective in London';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['seriesId']);
    }

    public function testJsonImportFile(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonFile = $dbPath . '/book/show/7112495.json';
        [$message, $errors] = $import->loadFromJsonFile($dbPath, $jsonFile);

        $expected = '/cache/goodreads/book/show/7112495.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testJsonImportPath(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $import = new JsonImport($dbFile, true);

        $jsonPath = 'book/show';
        [$message, $errors] = $import->loadFromPath($dbPath, $jsonPath);

        $expected = '/cache/goodreads/book/show/7112495.json - 1 files OK - 0 files Error';
        $this->assertStringContainsString($expected, $message);
        $this->assertCount(0, $errors);
    }

    public function testMatchGetBook(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $bookId = '2306655';
        $book = $match->getBook($bookId);

        $expected = '/book/show/[book_id]';
        $this->assertEquals($expected, $book['page']);
    }

    public function testMatchGetBookParsed(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $bookId = '2306655';
        $cacheFile = $cacheDir . '/goodreads/book/show/' . $bookId . '.htm';
        $content = file_get_contents($cacheFile);
        $result = $match->parseBookPage($bookId, $content);
        $book = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

        $expected = '/book/show/[book_id]';
        $this->assertEquals($expected, $book['page']);
    }

    public function testMatchGetSeries(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $seriesId = '102510-titan';
        $series = $match->getSeries($seriesId);

        $expected = 'Titan Series';
        $this->assertEquals($expected, $series[0][1]['title']);
    }

    public function testMatchGetSeriesParsed(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $seriesId = '102510-titan';
        $cacheFile = $cacheDir . '/goodreads/series/' . $seriesId . '.htm';
        $content = file_get_contents($cacheFile);
        $series = $match->parseSeriesPage($seriesId, $content);

        $expected = 'Titan Series';
        $this->assertEquals($expected, $series[0][1]['title']);
    }

    public function testMatchFindAuthors(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $query = 'David Mitchell';
        $result = $match->findAuthors($query);

        $expected = '6538289.David_Mitchell';
        $this->assertArrayHasKey($expected, $result);
    }

    public function testMatchFindAuthorsParsed(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $query = 'David Mitchell';
        $cacheFile = $cacheDir . '/goodreads/search/' . urlencode($query) . '.htm';
        $content = file_get_contents($cacheFile);
        $result = $match->parseSearchPage($query, $content);

        $expected = '6538289.David_Mitchell';
        $this->assertArrayHasKey($expected, $result);
    }

    public function testMatchFindAuthorId(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $author = ['name' => 'David Mitchell'];
        $result = $match->findAuthorId($author);

        $expected = '6538289.David_Mitchell';
        $this->assertEquals($expected, $result);
    }

    public function testMatchGetAuthor(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $authorId = '6538289.David_Mitchell';
        $author = $match->getAuthor($authorId);

        $expected = '6538289.David_Mitchell';
        $this->assertArrayHasKey($expected, $author);
    }

    public function testMatchGetAuthorParsed(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $authorId = '6538289.David_Mitchell';
        $cacheFile = $cacheDir . '/goodreads/author/list/' . $authorId . '.htm';
        $content = file_get_contents($cacheFile);
        $author = $match->parseAuthorPage($authorId, $content);

        $expected = '6538289.David_Mitchell';
        $this->assertArrayHasKey($expected, $author);
    }

    public function testMatchParseAuthorList(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $fileList = GoodReadsCache::getFiles($cacheDir . '/goodreads/author/list/', '*.htm');
        foreach ($fileList as $cacheFile) {
            $jsonFile = str_replace('.htm', '.json', $cacheFile);
            if (file_exists($jsonFile)) {
                continue;
            }
            $authorId = str_replace($cacheDir . '/goodreads/author/list/', '', $cacheFile);
            $authorId = str_replace('.htm', '', $authorId);
            $content = file_get_contents($cacheFile);
            $author = $match->parseAuthorPage($authorId, $content);
            $match->getCache()->saveCache($jsonFile, $author);
        }

        $expected = 1;
        $this->assertCount($expected, $fileList);
    }

    public function testMatchParseSearch(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $match = new GoodReadsMatch($cacheDir);

        $fileList = GoodReadsCache::getFiles($cacheDir . '/goodreads/search/', '*.htm');
        foreach ($fileList as $cacheFile) {
            $jsonFile = str_replace('.htm', '.json', $cacheFile);
            if (file_exists($jsonFile)) {
                continue;
            }
            $query = str_replace($cacheDir . '/goodreads/search/', '', $cacheFile);
            $query = str_replace('.htm', '', $query);
            $query = urldecode($query);
            $content = file_get_contents($cacheFile);
            $matched = $match->parseSearchPage($query, $content);
            $match->getCache()->saveCache($jsonFile, $matched);
        }

        $expected = 1;
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseAuthorResult(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/goodreads/author/list/', '*.json');
        foreach ($fileList as $cacheFile) {
            $authorId = str_replace($cacheDir . '/goodreads/author/list/', '', $cacheFile);
            $authorId = str_replace('.json', '', $authorId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $authors = $cache::parseSearch($matched);
        }

        $expected = count($cache->getAuthorIds());
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseSearchResult(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/goodreads/search/', '*.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/goodreads/search/', '', $cacheFile);
            $query = str_replace('.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $authors = $cache::parseSearch($matched);
        }

        $expected = count($cache->getSearchQueries());
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseSeriesResult(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/goodreads/series/', '*.json');
        foreach ($fileList as $cacheFile) {
            $seriesId = str_replace($cacheDir . '/goodreads/series/', '', $cacheFile);
            $seriesId = str_replace('.json', '', $seriesId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $series = $cache::parseSeries($matched);
            $series->setId($seriesId);
        }

        $expected = count($cache->getSeriesIds());
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseBook(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/goodreads/book/show/', '*.json');
        foreach ($fileList as $cacheFile) {
            $bookId = str_replace($cacheDir . '/goodreads/book/show/', '', $cacheFile);
            $bookId = str_replace('.json', '', $bookId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            $book = $cache::parseBook($matched);
        }

        $expected = count($cache->getBookIds());
        $this->assertCount($expected, $fileList);
    }

    public function testCheckBookLinks(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);
        $links = $db->checkBookLinks('goodreads');

        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $cacheFile = $cacheDir . '/goodreads/links.json';
        file_put_contents($cacheFile, json_encode($links, JSON_PRETTY_PRINT));
        $expected = count($cache->getBookIds());
        $this->assertCount($expected, $links);

        $books = [];
        $authors = [];
        $series = [];
        $invalid = [];
        $missing = [];
        foreach ($links as $id => $link) {
            if (empty($link['value'])) {
                continue;
            }
            if (empty($books[$link['value']])) {
                $cacheFile = $cache->getBook($link['value']);
                if (!$cache->hasCache($cacheFile)) {
                    continue;
                }
                $data = $cache->loadCache($cacheFile);
                if (empty($data)) {
                    $invalid[$link['value']] = $data;
                    $books[$link['value']] = new BookInfos();
                    continue;
                }
                try {
                    $book = $cache::parseBook($data);
                    $books[$link['value']] = GoodReadsImport::load($dbPath, $book);
                } catch (Exception $e) {
                    unset($data['locales']);
                    $invalid[$link['value']] = $data;
                    $books[$link['value']] = new BookInfos();
                    continue;
                }
            }
            $bookInfo = $books[$link['value']];
            // limit # of authors for matching here
            if (!empty($link['author']) && !empty($bookInfo->mAuthorIds) && count($bookInfo->mAuthorIds) < 4) {
                $authors[$link['author']] = array_filter($bookInfo->mAuthorIds);
            }
            // check for matching or missing series
            if (!empty($bookInfo->mSerieIds) && count($bookInfo->mSerieIds) < 4) {
                if (!empty($link['series'])) {
                    $series[$link['series']] = array_filter($bookInfo->mSerieIds);
                } elseif (!empty($bookInfo->mAuthorIds) && count($bookInfo->mAuthorIds) < 4) {
                    $missing[$link['book']] ??= [];
                    $missing[$link['book']][$link['author']] = array_filter($bookInfo->mSerieIds);
                }
            }
        }
        $seen = [];
        foreach ($authors as $authorId => $values) {
            // @todo check/map with author link
            foreach ($values as $value) {
                if (!empty($seen[$value])) {
                    continue;
                }
                $seen[$value] = 1;
                $cacheFile = $cache->getAuthor($value);
                if (!$cache->hasCache($cacheFile)) {
                    continue;
                }
                $data = $cache->loadCache($cacheFile);
                if (!empty($data)) {
                    $books = $cache::parseSearch($data);
                    // @todo check other book links
                }
            }
        }
        $seen = [];
        foreach ($series as $serieId => $values) {
            // @todo check/map with series link
            foreach ($values as $value) {
                if (!empty($seen[$value])) {
                    continue;
                }
                $seen[$value] = 1;
                $cacheFile = $cache->getSeries($value);
                if (!$cache->hasCache($cacheFile)) {
                    continue;
                }
                $data = $cache->loadCache($cacheFile);
                if (!empty($data)) {
                    $books = $cache::parseSeries($data);
                    // @todo check other book links
                }
            }
        }
        foreach ($missing as $bookId => $entries) {
            foreach ($entries as $authorId => $values) {
                foreach ($values as $value) {
                    if (!empty($seen[$value])) {
                        continue;
                    }
                    $seen[$value] = 1;
                    $cacheFile = $cache->getSeries($value);
                    if (!$cache->hasCache($cacheFile)) {
                        continue;
                    }
                    $data = $cache->loadCache($cacheFile);
                    if (!empty($data)) {
                        $books = $cache::parseSeries($data);
                        // @todo check other book links
                    }
                }
            }
        }
        $cacheFile = $cacheDir . '/goodreads/invalid.json';
        file_put_contents($cacheFile, json_encode($invalid, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/missing.json';
        file_put_contents($cacheFile, json_encode($missing, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/authors.json';
        file_put_contents($cacheFile, json_encode($authors, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/series.json';
        file_put_contents($cacheFile, json_encode($series, JSON_PRETTY_PRINT));
        $stats = $db->getStats();
        $expected = $stats['authors'];
        $this->assertCount($expected, $authors);
        $expected = $stats['series'];
        $this->assertCount($expected, $series);
    }

    #[Depends('testCheckBookLinks')]
    public function testCheckSeriesMatch(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);

        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $cacheFile = $cacheDir . '/goodreads/links.json';
        $links = json_decode(file_get_contents($cacheFile), true);
        $cacheFile = $cacheDir . '/goodreads/authors.json';
        $authors = json_decode(file_get_contents($cacheFile), true);
        $cacheFile = $cacheDir . '/goodreads/series.json';
        $series = json_decode(file_get_contents($cacheFile), true);

        $todo = [];
        foreach ($links as $id => $link) {
            if (empty($link['series']) || empty($series[$link['series']])) {
                continue;
            }
            if (empty($link['series_link'])) {
                $todo[$link['series']] = 1;
                continue;
            }
            // check against existing links too
            if (str_starts_with($link['series_link'], 'https://www.goodreads.com/series/')) {
                $todo[$link['series']] = 1;
                continue;
            }
        }
        $check = [];
        $matches = [];
        $partial = [];
        $mismatch = [];
        // @todo clean up duplicate series entries
        foreach ($todo as $seriesId => $one) {
            $info = $db->getSeries($seriesId);
            foreach ($info as $key => $data) {
                $info[$key]['slug'] = str_replace([' ', '&', '*', "'", ':', '.', ',', '(', ')'], ['-', '-', '-', '-', '', '', '', '', ''], strtolower($data['name']));
                foreach ($series[$seriesId] as $matchId) {
                    if (str_ends_with($matchId, $info[$key]['slug'])) {
                        $info[$key]['match'] = $matchId;
                        $matches[$seriesId] = $matchId;
                        if (empty($info[$key]['link'])) {
                            // @todo "UPDATE series SET link='https://www.goodreads.com/series/{$matchId}' WHERE id={$seriesId};\n";
                        } elseif (!str_ends_with($info[$key]['link'], '/' . $matchId)) {
                            $checkId = str_replace('https://www.goodreads.com/series/', '', $info[$key]['link']);
                            if (!in_array($checkId, $series[$seriesId])) {
                                $mismatch[$info[$key]['link']] = $info[$key];
                                $info[$key]['oops'] = $series[$seriesId];
                                // @todo "UPDATE series SET link='https://www.goodreads.com/series/{$matchId}' WHERE id={$seriesId};\n";
                            } else {
                                $info[$key]['options'] = $series[$seriesId];
                            }
                        }
                        break;
                    }
                }
                if (!empty($matches[$seriesId]) || strlen($info[$key]['slug']) < 4) {
                    break;
                }
                foreach ($series[$seriesId] as $matchId) {
                    if (str_contains($matchId, $info[$key]['slug'])) {
                        $info[$key]['partial'] = $matchId;
                        $partial[$seriesId] = $matchId;
                        if (empty($info[$key]['link'])) {
                            // @todo "UPDATE series SET link='https://www.goodreads.com/series/{$matchId}' WHERE id={$seriesId};\n";
                        } elseif (!str_ends_with($info[$key]['link'], '/' . $matchId)) {
                            $checkId = str_replace('https://www.goodreads.com/series/', '', $info[$key]['link']);
                            if (!in_array($checkId, $series[$seriesId])) {
                                $mismatch[$info[$key]['link']] = $info[$key];
                                $info[$key]['oops'] = $series[$seriesId];
                                // @todo "UPDATE series SET link='https://www.goodreads.com/series/{$matchId}' WHERE id={$seriesId};\n";
                            } else {
                                $info[$key]['options'] = $series[$seriesId];
                            }
                        }
                        break;
                    }
                }
            }
            $check[$seriesId] = [
                'info' => $info,
                'cache' => $series[$seriesId],
            ];
        }
        $check['matches'] = $matches;
        $check['partial'] = $partial;
        $check['mismatch'] = $mismatch;
        $cacheFile = $cacheDir . '/goodreads/check.json';
        file_put_contents($cacheFile, json_encode($check, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/matches.json';
        file_put_contents($cacheFile, json_encode($matches, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/partial.json';
        file_put_contents($cacheFile, json_encode($partial, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/mismatch.json';
        file_put_contents($cacheFile, json_encode($mismatch, JSON_PRETTY_PRINT));
        $this->assertTrue(count($matches) > 0);
    }
}
