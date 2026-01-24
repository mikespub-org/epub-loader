<?php

/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests\OpenLibrary;

use Marsender\EPubLoader\Metadata\OpenLibrary\OpenLibraryCache;
use Marsender\EPubLoader\Tests\BaseTestCase;

class OpenLibraryCacheTest extends BaseTestCase
{
    public function testCacheParseAuthorSearch(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new OpenLibraryCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/openlibrary/authors/', '*.en.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/openlibrary/authors/', '', $cacheFile);
            $query = str_replace('.en.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $authors = $cache::parseAuthorSearch($matched);
        }

        $expected = count($cache->getAuthorQueries('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseWorksByAuthor(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new OpenLibraryCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/openlibrary/works/author/', '*.en.100.json');
        foreach ($fileList as $cacheFile) {
            $authorId = str_replace($cacheDir . '/openlibrary/works/author/', '', $cacheFile);
            $authorId = str_replace('.en.100.json', '', $authorId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $works = $cache::parseWorkSearch($matched);
        }

        $expected = count($cache->getAuthorWorkIds('en', 100));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseWorksByTitle(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new OpenLibraryCache($cacheDir);

        $fileList = $cache::getFiles($cacheDir . '/openlibrary/works/title/', '*.json');
        foreach ($fileList as $cacheFile) {
            $query = str_replace($cacheDir . '/openlibrary/works/title/', '', $cacheFile);
            $query = str_replace('.json', '', $query);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            $works = $cache::parseWorkSearch($matched);
        }

        $expected = count($cache->getTitleQueries());
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseAuthorEntity(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new OpenLibraryCache($cacheDir);
        //$patterns = ['.remote_ids.properties' => '^\w+$'];
        //$patterns = ['.remoteIds.properties' => '^\w+$'];
        //$capture = new DataCapture($patterns);

        $fileList = $cache::getFiles($cacheDir . '/openlibrary/entities/', '*A.en.json');
        foreach ($fileList as $cacheFile) {
            $authorId = str_replace($cacheDir . '/openlibrary/entities/', '', $cacheFile);
            $authorId = str_replace('.en.json', '', $authorId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            //$capture->analyze($matched);
            $author = $cache::parseAuthorEntity($matched);
            //$capture->analyze($author);
        }
        //$cacheFile = $cacheDir . '/openlibrary/authorentity.report.json';
        //$report = $capture->report($cacheFile);

        $expected = count($cache->getAuthorIds('en'));
        $this->assertCount($expected, $fileList);
    }

    public function testCacheParseWorkEntity(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cache = new OpenLibraryCache($cacheDir);
        //$patterns = [];
        //$capture = new DataCapture($patterns);

        $fileList = $cache::getFiles($cacheDir . '/openlibrary/entities/', '*W.en.json');
        foreach ($fileList as $cacheFile) {
            $workId = str_replace($cacheDir . '/openlibrary/entities/', '', $cacheFile);
            $workId = str_replace('.en.json', '', $workId);
            $results = file_get_contents($cacheFile);
            $matched = json_decode($results, true);
            if (is_null($matched)) {
                continue;
            }
            //$capture->analyze($matched);
            $work = $cache::parseWorkEntity($matched);
            //$capture->analyze($work);
        }
        //$cacheFile = $cacheDir . '/openlibrary/workentity.report.json';
        //$report = $capture->report($cacheFile);

        $expected = count($cache->getWorkIds('en'));
        $this->assertCount($expected, $fileList);
    }
}
