<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\CalibreDbLoader;
use Marsender\EPubLoader\Metadata\BookInfos;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsCache;
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsImport;
use PHPUnit\Framework\Attributes\Depends;
use Exception;

class GoodReadsCheckTest extends BaseTestCase
{
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
        // for books with multiple authors or series?
        $this->assertTrue($expected <= count($links));

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
            } elseif (!empty($link['series'])) {
                $series[$link['series']] ??= [];
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
