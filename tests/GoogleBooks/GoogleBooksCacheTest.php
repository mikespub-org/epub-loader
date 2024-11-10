<?php
/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests\GoogleBooks;

use Marsender\EPubLoader\Metadata\GoogleBooks\GoogleBooksCache;
use Marsender\EPubLoader\Tests\BaseTestCase;

class GoogleBooksCacheTest extends BaseTestCase
{
    public function testCacheParseAuthors(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new GoogleBooksCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/google/authors/', '*.en.40.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/google/authors/', '', $cacheFile);
            $query = str_replace('.en.40.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $authors = $cache::parseSearch($matched);
        }

        $expected = count($cache->getAuthorQueries('en', 40));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseSeries(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new GoogleBooksCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/google/series/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/google/series/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $series = $cache::parseSearch($matched);
        }

        $expected = count($cache->getSeriesQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseTitles(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new GoogleBooksCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/google/titles/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/google/titles/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $titles = $cache::parseSearch($matched);
        }

        $expected = count($cache->getTitleQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseVolume(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new GoogleBooksCache(cacheDir: $cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/google/volumes/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $volumeId = str_replace($cacheDir . '/google/volumes/', '', $cacheFile);
            $volumeId = str_replace('.en.json', '', $volumeId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $volume = $cache::parseVolume($matched);
        }

        $expected = count($cache->getVolumeIds('en'));
        $this->assertCount($expected, $fileList);
    }
}
