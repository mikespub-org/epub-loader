<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\Import\DataCapture;
use Marsender\EPubLoader\Metadata\WikiData\WikiDataCache;

class WikiDataCacheTest extends BaseTestCase
{
    public function testCacheParseAuthorSearch(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/authors/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/wikidata/authors/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $authors = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getAuthorQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseWorksByAuthor(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/works/author/', '*.en.100.json');
        foreach ($fileList as $cacheFile) {
            $authorId = str_replace($cacheDir . '/wikidata/works/author/', '', $cacheFile);
            $authorId = str_replace('.en.100.json', '', $authorId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $works = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getAuthorWorkIds('en', 100));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseWorksByTitle(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/works/title/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/wikidata/works/title/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $works = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getTitleQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseWorksByName(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/works/name/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/wikidata/works/name/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $works = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getAuthorWorkQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseSeriesByAuthor(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/series/author/', '*.en.100.json');
        foreach ($fileList as $cacheFile) {
            $authorId = str_replace($cacheDir . '/wikidata/series/author/', '', $cacheFile);
            $authorId = str_replace('.en.100.json', '', $authorId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $works = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getAuthorSeriesIds('en', 100));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseSeriesByTitle(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/wikidata/series/title/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/wikidata/series/title/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $works = $cache::parseSearchResult($matched);
        }

        $expected = count($cache->getSeriesQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseEntity(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cache = new WikiDataCache($cacheDir);
        //$patterns = ['.properties' => '^P\d+$'];
        //$capture = new DataCapture($patterns);

        $properties = [];
        $fileList = $cache::getFiles($cacheDir . '/wikidata/entities/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $entityId = str_replace($cacheDir . '/wikidata/entities/', '', $cacheFile);
            $entityId = str_replace('.json', '', $entityId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            //$capture->analyze($matched);
            $work = $cache::parseEntity($matched);
            if (!empty($work) && $work['type'] == 'book') {
                //echo json_encode($work, JSON_PRETTY_PRINT) . "\n";
            }
            $instance = $matched['properties']['P31'] ?? [];
            $instance['values'] ??= [];
            foreach ($instance['values'] as $value) {
                $key = $value['id'] . ': ' . $value['label'];
                $properties[$key] ??= ['count' => 0, 'alone' => 0, 'properties' => []];
                $properties[$key]['count'] += 1;
                if (count($instance['values']) < 2) {
                    $properties[$key]['alone'] += 1;
                }
                foreach ($matched['properties'] as $pid => $property) {
                    $id = $property['id'] . ': ' . $property['label'];
                    $properties[$key]['properties'][$id] ??= 0;
                    $properties[$key]['properties'][$id] += 1;
                }
            }
        }
        //$cacheFile = $cacheDir . '/wikidata/entities.report.json';
        //$report = $capture->report($cacheFile);
        foreach ($properties as $key => $values) {
            arsort($values['properties'], SORT_NUMERIC);
            $properties[$key] = $values;
        }
        uasort($properties, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        $cacheFile = $cacheDir . '/wikidata/entities.properties.json';
        file_put_contents($cacheFile, json_encode($properties, JSON_PRETTY_PRINT));

        $expected = count($cache->getEntityIds('en'));
        $this->assertCount($expected, $fileList);
    }
}
