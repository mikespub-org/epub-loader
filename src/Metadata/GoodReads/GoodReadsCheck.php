<?php
/**
 * GoodReadsCheck class
 */

namespace Marsender\EPubLoader\Metadata\GoodReads;

use Marsender\EPubLoader\CalibreDbLoader;
use Marsender\EPubLoader\Metadata\BaseCheck;
use Marsender\EPubLoader\Models\BookInfo;
use Exception;

class GoodReadsCheck extends BaseCheck
{
    /** @var GoodReadsCache */
    protected $cache;
    /** @var GoodReadsMatch */
    protected $match;

    /**
     * Summary of setProperties
     * @param string $cacheDir
     * @param string $dbFile
     * @return void
     */
    public function setProperties($cacheDir, $dbFile)
    {
        $this->cache = new GoodReadsCache($cacheDir);
        $this->match = new GoodReadsMatch($cacheDir);
        $this->db = new CalibreDbLoader($dbFile);
        $this->prefix = '/goodreads';
    }

    /**
     * Summary of checkBookLinks
     * @param string $type
     * @throws \Exception
     * @return void
     */
    public function checkBookLinks($type = 'goodreads')
    {
        $this->errors = [];
        $limit = $this->db->limit;
        $this->db->limit = -1;
        $links = $this->db->checkBookLinks($type);
        $this->db->limit = $limit;

        $cacheFile = $this->cacheDir . $this->prefix . '/links.json';
        file_put_contents($cacheFile, json_encode($links, JSON_PRETTY_PRINT));
        $expected = count($this->cache->getBookIds());
        // for books with multiple authors or series?
        if ($expected > count($links)) {
            throw new Exception('More books in cache than book links in database - import first');
        }

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
                $bookInfo = $this->getCachedBookInfo($link['value']);
                if (empty($bookInfo)) {
                    $invalid[$link['value']] = $link;
                    $books[$link['value']] = new BookInfo();
                    continue;
                }
                $books[$link['value']] = $bookInfo;
            } else {
                $bookInfo = $books[$link['value']];
            }
            // limit # of authors for matching here
            if (!empty($link['author'])) {
                $authors[$link['author']] ??= [];
            }
            if (!empty($bookInfo->authors) && count($bookInfo->authors) < 4) {
                if (!empty($link['author'])) {
                    foreach (array_keys($bookInfo->authors) as $authorId) {
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
            if (!empty($bookInfo->series) && count($bookInfo->series) < 10) {
                $seriesInfo = $bookInfo->getSeriesInfo();
                if (!empty($link['series'])) {
                    foreach (array_keys($bookInfo->series) as $serieId) {
                        $series[$link['series']][$serieId] ??= 0;
                        $series[$link['series']][$serieId] += 1;
                    }
                } elseif (!empty($bookInfo->authors) && count($bookInfo->authors) < 4) {
                    $missing[$link['book']] ??= [];
                    foreach (array_keys($bookInfo->series) as $serieId) {
                        // @todo only valid for first series here!
                        $missing[$link['book']][$serieId] ??= $seriesInfo->index;
                    }
                }
            }
        }

        $seen = $this->checkCachedAuthors($authors);
        $seen = $this->checkCachedSeries($series);
        $seen = $this->checkCachedBookSeries($missing, $seen);

        $cacheFile = $this->cacheDir . $this->prefix . '/invalid.json';
        file_put_contents($cacheFile, json_encode($invalid, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/missing.json';
        file_put_contents($cacheFile, json_encode($missing, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/authors.json';
        file_put_contents($cacheFile, json_encode($authors, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/series.json';
        file_put_contents($cacheFile, json_encode($series, JSON_PRETTY_PRINT));

        $stats = $this->db->getStats();
        $expected = $stats['authors'];
        if ($expected > count($authors)) {
            throw new Exception('More authors in cache than in database - import first');
        }
        $expected = $stats['series'];
        if ($expected > count($series)) {
            throw new Exception('More series in cache than in database - import first');
        }
    }

    /**
     * Summary of checkCachedAuthors
     * @param array<mixed> $authors
     * @return array<mixed>
     */
    protected function checkCachedAuthors($authors)
    {
        $seen = [];
        foreach ($authors as $authorId => $values) {
            // @todo check/map with author link
            foreach ($values as $value => $count) {
                if (!empty($seen[$value])) {
                    continue;
                }
                $seen[$value] = 1;
                $cacheFile = $this->cache->getAuthor($value);
                if (!$this->cache->hasCache($cacheFile)) {
                    continue;
                }
                $data = $this->cache->loadCache($cacheFile);
                if (!empty($data)) {
                    $books = $this->cache::parseSearch($data);
                    // @todo check other book links
                }
            }
        }
        return $seen;
    }

    /**
     * Summary of checkCachedSeries
     * @param array<mixed> $series
     * @return array<mixed>
     */
    protected function checkCachedSeries($series)
    {
        $seen = [];
        foreach ($series as $serieId => $values) {
            // @todo check/map with series link
            foreach ($values as $value => $count) {
                if (!empty($seen[$value])) {
                    continue;
                }
                $seen[$value] = 1;
                $cacheFile = $this->cache->getSeries($value);
                if (!$this->cache->hasCache($cacheFile)) {
                    continue;
                }
                $data = $this->cache->loadCache($cacheFile);
                if (!empty($data)) {
                    $books = $this->cache::parseSeries($data);
                    // @todo check other book links
                }
            }
        }
        return $seen;
    }

    /**
     * Summary of checkCachedBookSeries
     * @param array<mixed> $missing
     * @param array<mixed> $seen from checkCachedSeries()
     * @return array<mixed>
     */
    protected function checkCachedBookSeries($missing, $seen)
    {
        foreach ($missing as $bookId => $entries) {
            foreach ($entries as $matchId => $value) {
                if (!empty($seen[$matchId])) {
                    continue;
                }
                $seen[$matchId] = 1;
                $cacheFile = $this->cache->getSeries($matchId);
                if (!$this->cache->hasCache($cacheFile)) {
                    continue;
                }
                $data = $this->cache->loadCache($cacheFile);
                if (!empty($data)) {
                    $books = $this->cache::parseSeries($data);
                    // @todo check other book links
                }
            }
        }
        return $seen;
    }

    /**
     * Summary of getCachedBookInfo
     * @param string $bookId
     * @return BookInfo|null
     */
    protected function getCachedBookInfo($bookId)
    {
        $cacheFile = $this->cache->getBook($bookId);
        if (!$this->cache->hasCache($cacheFile)) {
            return null;
        }
        $data = $this->cache->loadCache($cacheFile);
        if (empty($data)) {
            return null;
        }
        try {
            $bookResult = $this->cache::parseBook($data);
            $bookInfo = GoodReadsImport::load($this->dbPath, $bookResult, $this->cache);
        } catch (Exception $e) {
            $this->addError($bookId, $e->getMessage());
            return null;
        }
        return $bookInfo;
    }

    /**
     * Summary of checkAuthorMatch
     * @throws \Exception
     * @return void
     */
    public function checkAuthorMatch()
    {
        $this->errors = [];
        $cacheFile = $this->cacheDir . $this->prefix . '/links.json';
        $links = json_decode(file_get_contents($cacheFile), true);
        $cacheFile = $this->cacheDir . $this->prefix . '/authors.json';
        $authors = json_decode(file_get_contents($cacheFile), true);
        // sort author matches by descending book count
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
            if (str_starts_with((string) $link['author_link'], (string) $this->match::AUTHOR_URL)) {
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
        $authorList = $this->db->getAuthors(null, 'books');
        foreach ($todo as $authorId => $one) {
            $authorInfo = $authorList[$authorId];
            $authorInfo['slug'] = $this->match::authorSlug($authorInfo['name']);
            // exact match first
            foreach ($authors[$authorId] as $matchId => $count) {
                if (!str_ends_with((string) $matchId, (string) $authorInfo['slug'])) {
                    continue;
                }
                $authorInfo['match'] = $matchId;
                $matches[$authorId] = $matchId;
                $matchUrl = $this->match::AUTHOR_URL . $matchId;
                if (empty($authorInfo['link'])) {
                    $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                    break;
                }
                if (str_ends_with((string) $authorInfo['link'], '/' . $matchId)) {
                    // nothing to update here
                    break;
                }
                $checkId = str_replace($this->match::AUTHOR_URL, '', $authorInfo['link']);
                // unknown author match
                if (!array_key_exists($checkId, $authors[$authorId])) {
                    $mismatch[$authorInfo['link']] = $authorInfo;
                    $authorInfo['oops'] = $authors[$authorId];
                    $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                    break;
                }
                // pick the first author match
                $firstId = array_key_first($authors[$authorId]);
                if ($checkId != $firstId) {
                    $authorInfo['options'] = $authors[$authorId];
                    $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                }
                break;
            }
            if (!empty($matches[$authorId]) || strlen((string) $authorInfo['slug']) < 4) {
                $check[$authorId] = [
                    'info' => $authorInfo,
                    'cache' => $authors[$authorId],
                    'match' => $matches[$authorId] ?? $partial[$authorId] ?? null,
                ];
                continue;
            }
            // partial match next
            foreach ($authors[$authorId] as $matchId => $count) {
                if (!str_contains((string) $matchId, (string) $authorInfo['slug'])) {
                    continue;
                }
                $authorInfo['partial'] = $matchId;
                $partial[$authorId] = $matchId;
                $matchUrl = $this->match::AUTHOR_URL . $matchId;
                if (empty($authorInfo['link'])) {
                    $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                    break;
                }
                if (str_ends_with((string) $authorInfo['link'], '/' . $matchId)) {
                    // nothing to update here
                    break;
                }
                $checkId = str_replace($this->match::AUTHOR_URL, '', $authorInfo['link']);
                // unknown author match
                if (!array_key_exists($checkId, $authors[$authorId])) {
                    $mismatch[$authorInfo['link']] = $authorInfo;
                    $authorInfo['oops'] = $authors[$authorId];
                    $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                    break;
                }
                // pick the first author match
                $firstId = array_key_first($authors[$authorId]);
                if ($checkId != $firstId) {
                    $authorInfo['options'] = $authors[$authorId];
                    $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                }
                break;
            }
            $check[$authorId] = [
                'info' => $authorInfo,
                'cache' => $authors[$authorId],
                'match' => $matches[$authorId] ?? $partial[$authorId] ?? null,
            ];
        }
        if (!empty($update)) {
            $update = $this->db->wrapTrigger($update, 'authors', 'AFTER UPDATE ON');
        }

        // @todo grab other authors here too
        $bookCount = $this->db->getBookCountByAuthor();
        foreach ($authorList as $authorId => $author) {
            if (!empty($check[$authorId]) && !empty($check[$authorId]['match'])) {
                $matchId = $check[$authorId]['match'];
                try {
                    $this->match->getAuthor($matchId);
                } catch (Exception $e) {
                    $this->addError($matchId, $e->getMessage());
                }
                continue;
            }
            if (!empty($author['link'])) {
                if (str_starts_with((string) $author['link'], (string) $this->match::AUTHOR_URL)) {
                    $matchId = str_replace($this->match::AUTHOR_URL, '', $author['link']);
                    try {
                        $this->match->getAuthor($matchId);
                    } catch (Exception $e) {
                        $this->addError($matchId, $e->getMessage());
                    }
                }
                continue;
            }
            if (empty($bookCount[$authorId])) {
                continue;
            }
            $author['count'] = $bookCount[$authorId];
            $matched = $this->match->findAuthors($author['name']);
            // @todo Find author with highest books count!?
            uasort($matched, function ($a, $b) {
                return count($b['books']) <=> count($a['books']);
            });
            $slug = $this->match::authorSlug($author['name']);
            $matchIds = null;
            foreach ($matched as $id => $value) {
                if (str_ends_with((string) $value['id'], '.' . $slug)) {
                    $matchIds ??= [];
                    $matchIds[] = $value['id'];
                }
            }
            // less than 2 matches is easy
            if (empty($matchIds) || count($matchIds) < 2) {
                if (!empty($matchIds)) {
                    $matchUrl = $this->match::AUTHOR_URL . $matchIds[0];
                    $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                }
                foreach ($matched as $id => $value) {
                    $matched[$id]['books'] = count($value['books']);
                }
                $check[$authorId] ??= [];
                $check[$authorId] = array_merge($check[$authorId], ['info' => $author, 'matchIds' => $matchIds, 'matched' => $matched]);
                continue;
            }
            // check matching books
            $books = $this->db->getBooksByAuthor($authorId);
            $foundId = null;
            foreach ($books as $bookId => $book) {
                $title = $book['title'];
                if (str_contains((string) $title, ':')) {
                    $title = trim(explode(':', (string) $title)[0]);
                }
                if (str_contains((string) $title, '(')) {
                    $title = trim(explode('(', (string) $title)[0]);
                }
                $books[$bookId]['title'] = $title;
                foreach ($matchIds as $matchId) {
                    foreach ($matched[$matchId]['books'] as $item) {
                        if ($item['title'] == $title) {
                            $foundId = $matchId;
                            break 3;
                        }
                        if (str_contains((string) $item['title'], (string) $title)) {
                            $foundId = $matchId;
                            break 3;
                        }
                    }
                }
            }
            $update .= "# Options: " . implode(' ', $matchIds) . "\n";
            if ($foundId) {
                $matchUrl = $this->match::AUTHOR_URL . $foundId;
                $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
                foreach ($matched as $id => $value) {
                    $matched[$id]['books'] = count($value['books']);
                }
                $check[$authorId] ??= [];
                $check[$authorId] = array_merge($check[$authorId], ['info' => $author, 'matchIds' => $matchIds, 'matched' => $matched]);
                break;
            }
            // @todo get books by author from cache!?
            foreach ($books as $bookId => $book) {
                $title = $book['title'];
                foreach ($matchIds as $matchId) {
                    $bookList = $this->cache->getAuthorBooks($matchId) ?? [];
                    foreach ($bookList as $item) {
                        if ($item['title'] == $title) {
                            $foundId = $matchId;
                            break 3;
                        }
                        if (str_contains((string) $item['title'], (string) $title)) {
                            $foundId = $matchId;
                            break 3;
                        }
                    }
                }
            }
            if ($foundId) {
                $matchUrl = $this->match::AUTHOR_URL . $foundId;
                $update .= "UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
            } else {
                $matchUrl = $this->match::AUTHOR_URL . $matchIds[0];
                $update .= "# UPDATE authors SET link='{$matchUrl}' WHERE id={$authorId};\n";
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

        $cacheFile = $this->cacheDir . $this->prefix . '/authors_check.json';
        file_put_contents($cacheFile, json_encode($check, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/authors_matches.json';
        file_put_contents($cacheFile, json_encode($matches, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/authors_partial.json';
        file_put_contents($cacheFile, json_encode($partial, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/authors_mismatch.json';
        file_put_contents($cacheFile, json_encode($mismatch, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/authors_update.sql';
        file_put_contents($cacheFile, $update);

        if (empty($matches)) {
            throw new Exception('No author matches found');
        }
    }

    /**
     * Summary of checkSeriesMatch
     * @throws \Exception
     * @return void
     */
    public function checkSeriesMatch()
    {
        $this->errors = [];
        $cacheFile = $this->cacheDir . $this->prefix . '/links.json';
        $links = json_decode(file_get_contents($cacheFile), true);
        $cacheFile = $this->cacheDir . $this->prefix . '/series.json';
        $series = json_decode(file_get_contents($cacheFile), true);
        // sort series matches by descending book count
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
            if (str_starts_with((string) $link['series_link'], (string) $this->match::SERIES_URL)) {
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
            $info = $this->db->getSeries($seriesId);
            foreach ($info as $key => $data) {
                $info[$key]['slug'] = $this->match::seriesSlug($data['name']);
                // exact match first
                foreach ($series[$seriesId] as $matchId => $count) {
                    if (!str_ends_with((string) $matchId, (string) $info[$key]['slug'])) {
                        continue;
                    }
                    $info[$key]['match'] = $matchId;
                    $matches[$seriesId] = $matchId;
                    $matchUrl = $this->match::SERIES_URL . $matchId;
                    if (empty($info[$key]['link'])) {
                        $update .= "UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                        break;
                    }
                    if (str_ends_with((string) $info[$key]['link'], '/' . $matchId)) {
                        // nothing to update here
                        break;
                    }
                    $checkId = str_replace($this->match::SERIES_URL, '', $info[$key]['link']);
                    // unknown series match
                    if (!array_key_exists($checkId, $series[$seriesId])) {
                        $mismatch[$info[$key]['link']] = $info[$key];
                        $info[$key]['oops'] = $series[$seriesId];
                        $update .= "UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                        break;
                    }
                    // pick the first series match
                    $firstId = array_key_first($series[$seriesId]);
                    if ($checkId != $firstId) {
                        $info[$key]['options'] = $series[$seriesId];
                        $update .= "# UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                    }
                    break;
                }
                if (!empty($matches[$seriesId]) || strlen((string) $info[$key]['slug']) < 4) {
                    break;
                }
                // partial match next
                foreach ($series[$seriesId] as $matchId => $count) {
                    if (!str_contains((string) $matchId, (string) $info[$key]['slug'])) {
                        continue;
                    }
                    $info[$key]['partial'] = $matchId;
                    $partial[$seriesId] = $matchId;
                    $matchUrl = $this->match::SERIES_URL . $matchId;
                    if (empty($info[$key]['link'])) {
                        $update .= "UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                        break;
                    }
                    if (str_ends_with((string) $info[$key]['link'], '/' . $matchId)) {
                        // nothing to update here
                        break;
                    }
                    $checkId = str_replace($this->match::SERIES_URL, '', $info[$key]['link']);
                    // unknown series match
                    if (!array_key_exists($checkId, $series[$seriesId])) {
                        $mismatch[$info[$key]['link']] = $info[$key];
                        $info[$key]['oops'] = $series[$seriesId];
                        $update .= "UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                        break;
                    }
                    // pick the first series match
                    $firstId = array_key_first($series[$seriesId]);
                    if ($checkId != $firstId) {
                        $info[$key]['options'] = $series[$seriesId];
                        $update .= "# UPDATE series SET link='{$matchUrl}' WHERE id={$seriesId};\n";
                    }
                    break;
                }
            }
            $check[$seriesId] = [
                'info' => $info,
                'cache' => $series[$seriesId],
                'match' => $matches[$seriesId] ?? $partial[$seriesId] ?? null,
            ];
        }
        if (!empty($update)) {
            $update = $this->db->wrapTrigger($update, 'series', 'AFTER UPDATE ON');
        }

        // @todo grab other series here too
        $seriesLinks = $this->db->getSeriesLinks();
        foreach ($seriesLinks as $seriesId => $link) {
            if (!empty($check[$seriesId]) && !empty($check[$seriesId]['match'])) {
                $matchId = $check[$seriesId]['match'];
                try {
                    $this->match->getSeries($matchId);
                } catch (Exception $e) {
                    $this->addError($matchId, $e->getMessage());
                }
                continue;
            }
            if (!empty($link)) {
                if (str_starts_with((string) $link, (string) $this->match::SERIES_URL)) {
                    $matchId = str_replace($this->match::SERIES_URL, '', $link);
                    try {
                        $this->match->getSeries($matchId);
                    } catch (Exception $e) {
                        $this->addError($matchId, $e->getMessage());
                    }
                }
                continue;
            }
            // @todo
        }
        $check['matches'] = $matches;
        $check['partial'] = $partial;
        $check['mismatch'] = $mismatch;

        $cacheFile = $this->cacheDir . $this->prefix . '/series_check.json';
        file_put_contents($cacheFile, json_encode($check, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/series_matches.json';
        file_put_contents($cacheFile, json_encode($matches, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/series_partial.json';
        file_put_contents($cacheFile, json_encode($partial, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/series_mismatch.json';
        file_put_contents($cacheFile, json_encode($mismatch, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/series_update.sql';
        file_put_contents($cacheFile, $update);

        if (empty($matches)) {
            throw new Exception('No series matches found');
        }
    }

    /**
     * Summary of checkBookSeriesMatch
     * @throws \Exception
     * @return void
     */
    public function checkBookSeriesMatch()
    {
        $this->errors = [];
        $cacheFile = $this->cacheDir . $this->prefix . '/missing.json';
        $missing = json_decode(file_get_contents($cacheFile), true);

        $series = [];
        $seriesLinks = $this->db->getSeriesLinks();
        foreach ($seriesLinks as $seriesId => $link) {
            if (!empty($link) && str_starts_with((string) $link, (string) $this->match::SERIES_URL)) {
                $matchId = str_replace($this->match::SERIES_URL, '', $link);
                $series[$matchId] = $seriesId;
            }
        }

        $todo = [];
        $replace = '';
        foreach ($missing as $bookId => $missed) {
            $found = false;
            foreach ($missed as $matchId => $index) {
                if (empty($series[$matchId])) {
                    continue;
                }
                $seriesId = $series[$matchId];
                $replace .= "REPLACE INTO books_series_link(book, series) VALUES({$bookId}, {$seriesId});\n";
                // @todo only valid for first series here!
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
            if (!$found) {
                $todo[$bookId] = $missed;
            }
        }
        if (!empty($replace)) {
            $replace = $this->db->wrapTrigger($replace, 'books', 'AFTER UPDATE ON');
        }

        // @todo grab other series here too
        $seen = [];
        // grab single series first
        foreach ($todo as $bookId => $missed) {
            if (count($missed) == 1) {
                $matchId = array_key_first($missed);
                $seen[$matchId] ??= 0;
                $seen[$matchId] += 1;
            }
        }
        // then grab first of multiple series found
        foreach ($todo as $bookId => $missed) {
            if (count($missed) == 1) {
                continue;
            }
            $foundId = null;
            foreach ($missed as $matchId => $index) {
                if (array_key_exists($matchId, $seen)) {
                    $foundId = $matchId;
                    break;
                }
            }
            if (empty($foundId)) {
                $matchId = array_key_first($missed);
                $seen[$matchId] ??= 0;
                $seen[$matchId] += 1;
            }
        }
        $insert = '';
        foreach ($seen as $matchId => $count) {
            if (!empty($series[$matchId])) {
                continue;
            }
            try {
                $data = $this->match->getSeries($matchId);
            } catch (Exception $e) {
                $this->addError($matchId, $e->getMessage());
            }
            if (empty($data)) {
                continue;
            }
            $result = $this->cache::parseSeries($data);
            // escape single quotes by using two single quotes here
            $name = str_replace("'", "''", $result->getTitle() ?? "Series $matchId");
            $sort = BookInfo::getTitleSort($name);
            $sort = BookInfo::getSortString($sort);
            $matchUrl = $this->match::SERIES_URL . $matchId;
            $insert .= "INSERT INTO series(name, sort, link) VALUES('{$name}', '{$sort}', '{$matchUrl}');\n";
        }
        if (!empty($insert)) {
            $insert = $this->db->wrapTrigger($insert, 'series', 'AFTER INSERT ON');
        }

        $cacheFile = $this->cacheDir . $this->prefix . '/books_series_todo.json';
        file_put_contents($cacheFile, json_encode($todo, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/books_series_replace.sql';
        file_put_contents($cacheFile, $replace);
        $cacheFile = $this->cacheDir . $this->prefix . '/series_insert.sql';
        file_put_contents($cacheFile, $insert);

        if (!empty($missing)) {
            throw new Exception('Missing book series found');
        }
    }

    /**
     * Summary of checkBookIdentifiers
     * @throws \Exception
     * @return void
     */
    public function checkBookIdentifiers()
    {
        $this->errors = [];
        $authorList = $this->db->getAuthors(null, 'books');

        $todo = [];
        foreach ($authorList as $authorId => $author) {
            if (empty($author['link']) || !str_starts_with((string) $author['link'], (string) $this->match::AUTHOR_URL)) {
                continue;
            }
            $matchId = str_replace($this->match::AUTHOR_URL, '', $author['link']);
            $bookList = $this->cache->getAuthorBooks($matchId) ?? [];
            if (empty($bookList)) {
                continue;
            }
            $books = $this->db->getBooksByAuthor($authorId);
            foreach ($books as $bookId => $book) {
                if (!empty($todo[$bookId])) {
                    continue;
                }
                if (!empty($book['identifiers']['goodreads']) && !empty($book['identifiers']['goodreads']['value'])) {
                    continue;
                }
                $title = $book['title'];
                //if (str_contains($title, ':')) {
                //    $title = trim(explode(':', $title)[0]);
                //}
                //if (str_contains($title, '(')) {
                //    $title = trim(explode('(', $title)[0]);
                //}
                $foundId = null;
                foreach ($bookList as $item) {
                    if ($item['title'] == $title) {
                        $foundId = $item['id'];
                        break;
                    }
                }
                if (empty($foundId)) {
                    foreach ($bookList as $item) {
                        if (str_contains((string) $item['title'], (string) $title)) {
                            $foundId = $item['id'];
                            break;
                        }
                    }
                }
                if (!empty($foundId)) {
                    $todo[$bookId] = $foundId;
                }
            }
        }

        $insert = '';
        foreach ($todo as $bookId => $matchId) {
            $matchId = $this->match::bookid($matchId);
            $insert .= "INSERT INTO identifiers(book, type, val) VALUES('{$bookId}', 'goodreads', '{$matchId}');\n";
        }

        $cacheFile = $this->cacheDir . $this->prefix . '/identifiers_todo.json';
        file_put_contents($cacheFile, json_encode($todo, JSON_PRETTY_PRINT));
        $cacheFile = $this->cacheDir . $this->prefix . '/identifiers_insert.sql';
        file_put_contents($cacheFile, $insert);

        if (!empty($todo)) {
            throw new Exception('Missing books found');
        }
    }

    /**
     * Summary of getInvalidBooks
     * @return void
     */
    public function getInvalidBooks()
    {
        $cacheFile = $this->cacheDir . $this->prefix . '/invalid.json';
        $invalid = json_decode(file_get_contents($cacheFile), true);
        foreach ($invalid as $matchId => $info) {
            try {
                $this->match->getBook($matchId);
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }
    }
}
