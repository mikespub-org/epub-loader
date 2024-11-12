<?php
/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\Metadata\BaseCache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

class BaseCacheTest extends BaseTestCase
{
    /** @var array<mixed> */
    public static array $cacheEntries = [];

    public function testGetCacheStats(): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';

        $stats = BaseCache::getCacheStats($cacheDir);

        $cacheFile = $cacheDir . '/caches.json';
        $content = file_get_contents($cacheFile);
        $expected = json_decode($content, true);

        $this->assertEquals($expected, $stats);
    }

    /** @return array<mixed> */
    public static function cacheEntriesProvider(): array
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cacheFile = $cacheDir . '/caches.json';
        $content = file_get_contents($cacheFile);
        $stats = json_decode($content, true);
        $data = [];
        foreach ($stats as $cacheName => $cacheStats) {
            foreach ($cacheStats as $cacheType => $count) {
                $data[] = [$cacheName, $cacheType, $count];
            }
        }
        return $data;
    }

    /**
     * @param string $cacheName
     * @param string $cacheType
     * @param int $count
     */
    #[DataProvider('cacheEntriesProvider')]
    public function testGetCacheEntries($cacheName, $cacheType, $count): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';

        $entries = BaseCache::getCacheEntries($cacheDir, $cacheName, $cacheType);

        $cacheEntry = array_key_first($entries);
        $actionUrl = "/phpunit/caches/0";
        $urlPrefix = "{$actionUrl}/{$cacheName}/";
        if ([$cacheName, $cacheType] == ['GoodReads', 'search']) {
            self::$cacheEntries[] = [$cacheName, $cacheType, urldecode($cacheEntry), $urlPrefix];
        } else {
            self::$cacheEntries[] = [$cacheName, $cacheType, $cacheEntry, $urlPrefix];
        }

        $cacheFile = $cacheDir . '/entries.json';
        file_put_contents($cacheFile, json_encode(self::$cacheEntries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $expected = ($count > 500) ? 500 : $count;

        $this->assertCount($expected, $entries);
    }

    /** @return array<mixed> */
    public static function cacheEntryProvider(): array
    {
        $cacheDir = dirname(__DIR__) . '/cache';
        $cacheFile = $cacheDir . '/entries.json';
        $content = file_get_contents($cacheFile);

        return json_decode($content, true);
    }

    /**
     * @param string $cacheName
     * @param string $cacheType
     * @param string $cacheEntry
     * @param string $urlPrefix
     */
    #[Depends('testGetCacheEntries')]
    #[DataProvider('cacheEntryProvider')]
    public function testGetCacheEntry($cacheName, $cacheType, $cacheEntry, $urlPrefix): void
    {
        $cacheDir = dirname(__DIR__) . '/cache';

        $entry = BaseCache::getCacheEntry($cacheDir, $cacheName, $cacheType, $cacheEntry, $urlPrefix);

        $this->assertIsArray($entry);
    }
}
