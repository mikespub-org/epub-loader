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
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsMatch;
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
            if (!empty($link['author'])) {
                $authors[$link['author']] ??= [];
            }
            if (!empty($bookInfo->mAuthorIds) && count($bookInfo->mAuthorIds) < 4) {
                if (!empty($link['author'])) {
                    foreach (array_filter($bookInfo->mAuthorIds) as $authorId) {
                        $authors[$link['author']][$authorId] ??= 0;
                        $authors[$link['author']][$authorId] += 1;
                    }
                } else {
                    // @todo nothing
                }
            }
            // check for matching or missing series
            if (!empty($link['series'])) {
                $series[$link['series']] ??= [];
            }
            if (!empty($bookInfo->mSerieIds) && count($bookInfo->mSerieIds) < 10) {
                if (!empty($link['series'])) {
                    foreach (array_filter($bookInfo->mSerieIds) as $serieId) {
                        $series[$link['series']][$serieId] ??= 0;
                        $series[$link['series']][$serieId] += 1;
                    }
                } elseif (!empty($bookInfo->mAuthorIds) && count($bookInfo->mAuthorIds) < 4) {
                    $missing[$link['book']] ??= [];
                    foreach (array_filter($bookInfo->mSerieIds) as $serieId) {
                        // @todo only valid for first series here!
                        $missing[$link['book']][$serieId] ??= $bookInfo->mSerieIndex;
                    }
                }
            }
        }
        $seen = [];
        foreach ($authors as $authorId => $values) {
            // @todo check/map with author link
            foreach ($values as $value => $count) {
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
            foreach ($values as $value => $count) {
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
            foreach ($entries as $matchId => $value) {
                if (!empty($seen[$matchId])) {
                    continue;
                }
                $seen[$matchId] = 1;
                $cacheFile = $cache->getSeries($matchId);
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
    public function testCheckAuthorMatch(): void
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
        foreach ($authors as $authorId => $matches) {
            arsort($matches, SORT_NUMERIC);
            $authors[$authorId] = $matches;
        }

        $todo = [];
        foreach ($links as $id => $link) {
            if (empty($link['author']) || empty($authors[$link['author']])) {
                continue;
            }
            if (empty($link['author_link'])) {
                $todo[$link['author']] = 1;
                continue;
            }
            // check against existing links too
            if (str_starts_with($link['author_link'], GoodReadsMatch::AUTHOR_URL)) {
                $todo[$link['author']] = 1;
                continue;
            }
        }
        $check = [];
        $matches = [];
        $partial = [];
        $mismatch = [];
        $update = '';
        // @todo clean up duplicate authors entries
        $authorList = $db->getAuthors(null, 'books');
        foreach ($todo as $authorId => $one) {
            $authorInfo = $authorList[$authorId];
            $authorInfo['slug'] = preg_replace('/__+/', '_', str_replace([' ', '.', "'"], ['_', '_', '_'], $authorInfo['name']));
            foreach ($authors[$authorId] as $matchId => $count) {
                if (str_ends_with($matchId, $authorInfo['slug'])) {
                    $authorInfo['match'] = $matchId;
                    $matches[$authorId] = $matchId;
                    $matchUrl = GoodReadsMatch::AUTHOR_URL . $matchId;
                    if (empty($authorInfo['link'])) {
                        $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                    } elseif (!str_ends_with($authorInfo['link'], '/' . $matchId)) {
                        $checkId = str_replace(GoodReadsMatch::AUTHOR_URL, '', $authorInfo['link']);
                        if (!array_key_exists($checkId, $authors[$authorId])) {
                            $mismatch[$authorInfo['link']] = $authorInfo;
                            $authorInfo['oops'] = $authors[$authorId];
                            $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                        } else {
                            $firstId = array_key_first($authors[$authorId]);
                            if ($checkId != $firstId) {
                                $authorInfo['options'] = $authors[$authorId];
                                $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                            }
                        }
                    }
                    break;
                }
            }
            if (!empty($matches[$authorId]) || strlen($authorInfo['slug']) < 4) {
                $check[$authorId] = [
                    'info' => $authorInfo,
                    'cache' => $authors[$authorId],
                    'match' => $matches[$authorId] ?? $partial[$authorId] ?? null,
                ];
                continue;
            }
            foreach ($authors[$authorId] as $matchId => $count) {
                if (str_contains($matchId, $authorInfo['slug'])) {
                    $authorInfo['partial'] = $matchId;
                    $partial[$authorId] = $matchId;
                    $matchUrl = GoodReadsMatch::AUTHOR_URL . $matchId;
                    if (empty($authorInfo['link'])) {
                        $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                    } elseif (!str_ends_with($authorInfo['link'], '/' . $matchId)) {
                        $checkId = str_replace(GoodReadsMatch::AUTHOR_URL, '', $authorInfo['link']);
                        if (!array_key_exists($checkId, $authors[$authorId])) {
                            $mismatch[$authorInfo['link']] = $authorInfo;
                            $authorInfo['oops'] = $authors[$authorId];
                            $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                        } else {
                            $firstId = array_key_first($authors[$authorId]);
                            if ($checkId != $firstId) {
                                $authorInfo['options'] = $authors[$authorId];
                                $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                            }
                        }
                    }
                    break;
                }
            }
            $check[$authorId] = [
                'info' => $authorInfo,
                'cache' => $authors[$authorId],
                'match' => $matches[$authorId] ?? $partial[$authorId] ?? null,
            ];
        }
        // @todo grab other authors here too
        $goodreads = new GoodReadsMatch($cacheDir);
        $bookCount = $db->getBookCountByAuthor();
        foreach ($authorList as $authorId => $author) {
            if (!empty($check[$authorId]) && !empty($check[$authorId]['match'])) {
                continue;
            }
            if (!empty($author['link'])) {
                continue;
            }
            if (empty($bookCount[$authorId])) {
                continue;
            }
            $author['count'] = $bookCount[$authorId];
            $matched = $goodreads->findAuthors($author['name']);
            // @todo Find author with highest books count!?
            uasort($matched, function ($a, $b) {
                return count($b['books']) <=> count($a['books']);
            });
            $slug = preg_replace('/__+/', '_', str_replace([' ', '.', "'"], ['_', '_', '_'], $author['name']));
            $matchIds = null;
            foreach ($matched as $id => $value) {
                if (str_ends_with($value['id'], '.' . $slug)) {
                    $matchIds ??= [];
                    $matchIds[] = $value['id'];
                }
            }
            if (!empty($matchIds)) {
                if (count($matchIds) < 2) {
                    $matchUrl = GoodReadsMatch::AUTHOR_URL . $matchIds[0];
                    $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                } else {
                    // @todo check matching books
                    $matchUrl = GoodReadsMatch::AUTHOR_URL . $matchIds[0];
                    $update .= "# Options: " . implode(' ', $matchIds) . "\n";
                    $update .= "# UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                }
            }
            foreach ($matched as $id => $value) {
                $matched[$id]['books'] = count($value['books']);
            }
            $check[$authorId] ??= [];
            $check[$authorId] = array_merge($check[$authorId], ['info' => $author, 'matchIds' => $matchIds, 'matched' => $matched]);
        }
        $check['matches'] = $matches;
        $check['partial'] = $partial;
        $check['mismatch'] = $mismatch;
        $cacheFile = $cacheDir . '/goodreads/authors_check.json';
        file_put_contents($cacheFile, json_encode($check, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/authors_matches.json';
        file_put_contents($cacheFile, json_encode($matches, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/authors_partial.json';
        file_put_contents($cacheFile, json_encode($partial, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/authors_mismatch.json';
        file_put_contents($cacheFile, json_encode($mismatch, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/authors_update.sql';
        file_put_contents($cacheFile, $update);
        $this->assertTrue(count($matches) > 0);
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
        $cacheFile = $cacheDir . '/goodreads/series.json';
        $series = json_decode(file_get_contents($cacheFile), true);
        foreach ($series as $seriesId => $matches) {
            arsort($matches, SORT_NUMERIC);
            $series[$seriesId] = $matches;
        }

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
            if (str_starts_with($link['series_link'], GoodReadsMatch::SERIES_URL)) {
                $todo[$link['series']] = 1;
                continue;
            }
        }
        $check = [];
        $matches = [];
        $partial = [];
        $mismatch = [];
        $update = '';
        // @todo clean up duplicate series entries
        foreach ($todo as $seriesId => $one) {
            $info = $db->getSeries($seriesId);
            foreach ($info as $key => $data) {
                $info[$key]['slug'] = preg_replace('/--+/', '-', str_replace([' ', '&', '*', "'", ':', '.', ',', '(', ')'], ['-', '-', '-', '-', '', '', '', '', ''], strtolower($data['name'])));
                foreach ($series[$seriesId] as $matchId => $count) {
                    if (str_ends_with($matchId, $info[$key]['slug'])) {
                        $info[$key]['match'] = $matchId;
                        $matches[$seriesId] = $matchId;
                        $matchUrl = GoodReadsMatch::SERIES_URL . $matchId;
                        if (empty($info[$key]['link'])) {
                            $update .= "UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                        } elseif (!str_ends_with($info[$key]['link'], '/' . $matchId)) {
                            $checkId = str_replace(GoodReadsMatch::SERIES_URL, '', $info[$key]['link']);
                            if (!array_key_exists($checkId, $series[$seriesId])) {
                                $mismatch[$info[$key]['link']] = $info[$key];
                                $info[$key]['oops'] = $series[$seriesId];
                                $update .= "UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                            } else {
                                $firstId = array_key_first($series[$seriesId]);
                                if ($checkId != $firstId) {
                                    $info[$key]['options'] = $series[$seriesId];
                                    $update .= "UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                                }
                            }
                        }
                        break;
                    }
                }
                if (!empty($matches[$seriesId]) || strlen($info[$key]['slug']) < 4) {
                    break;
                }
                foreach ($series[$seriesId] as $matchId => $count) {
                    if (str_contains($matchId, $info[$key]['slug'])) {
                        $info[$key]['partial'] = $matchId;
                        $partial[$seriesId] = $matchId;
                        $matchUrl = GoodReadsMatch::SERIES_URL . $matchId;
                        if (empty($info[$key]['link'])) {
                            $update .= "UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                        } elseif (!str_ends_with($info[$key]['link'], '/' . $matchId)) {
                            $checkId = str_replace(GoodReadsMatch::SERIES_URL, '', $info[$key]['link']);
                            if (!array_key_exists($checkId, $series[$seriesId])) {
                                $mismatch[$info[$key]['link']] = $info[$key];
                                $info[$key]['oops'] = $series[$seriesId];
                                $update .= "UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                            } else {
                                $firstId = array_key_first($series[$seriesId]);
                                if ($checkId != $firstId) {
                                    $info[$key]['options'] = $series[$seriesId];
                                    $update .= "UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                                }
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
        $cacheFile = $cacheDir . '/goodreads/series_check.json';
        file_put_contents($cacheFile, json_encode($check, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/series_matches.json';
        file_put_contents($cacheFile, json_encode($matches, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/series_partial.json';
        file_put_contents($cacheFile, json_encode($partial, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/series_mismatch.json';
        file_put_contents($cacheFile, json_encode($mismatch, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/series_update.sql';
        file_put_contents($cacheFile, $update);
        $this->assertTrue(count($matches) > 0);
    }

    #[Depends('testCheckBookLinks')]
    public function testCheckMissingMatch(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);

        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new GoodReadsCache($cacheDir);

        $cacheFile = $cacheDir . '/goodreads/links.json';
        $links = json_decode(file_get_contents($cacheFile), true);
        $cacheFile = $cacheDir . '/goodreads/missing.json';
        $missing = json_decode(file_get_contents($cacheFile), true);

        $series = [];
        foreach ($links as $id => $link) {
            if (empty($link['series']) || empty($link['series_link'])) {
                continue;
            }
            if (str_starts_with($link['series_link'], GoodReadsMatch::SERIES_URL)) {
                $matchId = str_replace(GoodReadsMatch::SERIES_URL, '', $link['series_link']);
                $series[$matchId] = $link['series'];
            }
        }

        $todo = [];
        $replace = '';
        foreach ($missing as $bookId => $missed) {
            $found = false;
            foreach ($missed as $matchId => $index) {
                if (array_key_exists($matchId, $series)) {
                    $seriesId = $series[$matchId];
                    $replace .= "REPLACE INTO books_series_link(book, series) VALUES({$bookId}, {$seriesId});\n";
                    // format index as float
                    $replace .= "# Series Index: {$index}\n";
                    $index = str_replace(['-', '·'], ['.', '.'], $index);
                    if (str_contains($index, ',')) {
                        $index = explode(',', $index)[0];
                    }
                    $index = (float) $index;
                    $replace .= "UPDATE books SET series_index={$index} WHERE id={$bookId};\n";
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $todo[$bookId] = $missed;
            }
        }
        $cacheFile = $cacheDir . '/goodreads/books_series_todo.json';
        file_put_contents($cacheFile, json_encode($todo, JSON_PRETTY_PRINT));
        $cacheFile = $cacheDir . '/goodreads/books_series_replace.sql';
        file_put_contents($cacheFile, $replace);
        $this->assertTrue(count($missing) > 0);
    }
}
