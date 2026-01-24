<?php

/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\CalibreDbLoader;

class CalibreDbTest extends BaseTestCase
{
    public function testGetStats(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);
        $stats = $db->getStats();

        $cacheFile = dirname($dbPath) . '/sizes.json';
        $content = file_get_contents($cacheFile);
        $result = json_decode($content, true);
        $expected = $result[$dbPath];
        unset($expected['count']);

        $this->assertEquals($expected, $stats);
    }

    public function testGetAuthorPaging(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);
        $paging = $db->getAuthorPaging();

        $expected = null;
        $this->assertEquals($expected, $paging);

        $db->limit = 2;
        $paging = $db->getAuthorPaging();

        $expected = [
            'first' => '',
            'prev' => '',
            'next' => 'offset=2',
            'last' => 'offset=36',
        ];
        $this->assertEquals($expected, $paging);
    }

    public function testGetBookPaging(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);
        $paging = $db->getBookPaging();

        $expected = null;
        $this->assertEquals($expected, $paging);

        $db->limit = 2;
        $paging = $db->getBookPaging('name', 2);

        $expected = [
            'first' => 'sort=name&offset=0',
            'prev' => 'sort=name&offset=0',
            'next' => 'sort=name&offset=4',
            'last' => 'sort=name&offset=144',
        ];
        $this->assertEquals($expected, $paging);
    }

    public function testGetSeriesPaging(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);
        $paging = $db->getSeriesPaging();

        $expected = null;
        $this->assertEquals($expected, $paging);

        $db->limit = 1;
        $paging = $db->getSeriesPaging('name', 0);

        $expected = [
            'first' => '',
            'prev' => '',
            'next' => 'sort=name&offset=1',
            'last' => 'sort=name&offset=40',
        ];
        $this->assertEquals($expected, $paging);
    }

    public function testGetBooksBySeries(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);
        $books = $db->getBooksBySeries(1);

        $expected = 8;
        $this->assertCount($expected, $books);
    }

    public function testCheckBookLinks(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);

        $books = $db->checkBookLinks('goodreads');
        $expected = 145;
        $this->assertCount($expected, $books);

        $cacheFile = $dbPath . '/expected.json';
        $expectedBooks = [];
        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $expectedBooks = json_decode($content, true);
        }

        $books = $db->checkBookLinks('goodreads', 1);
        if (empty($expectedBooks)) {
            file_put_contents($cacheFile, json_encode($books, JSON_PRETTY_PRINT));
            $expectedBooks = $books;
        }
        $expected = count($expectedBooks);
        $this->assertCount($expected, $books);

        // filter books for author = 1 and series = 1
        $expectedBooks = array_filter($expectedBooks, function ($book) {
            return $book['author'] == 1 && $book['series'] == 1;
        });
        $expectedBooks = array_values($expectedBooks);
        $expected = 8;
        $this->assertCount($expected, $expectedBooks);
        // get bookId's and add extra dummy
        $bookIdList = array_map(function ($book) {
            return $book['book'];
        }, $expectedBooks);
        $bookIdList[] = 1234567890;
        // get valueId's and add extra dummy
        $valueIdList = array_map(function ($book) {
            return $book['value'];
        }, $expectedBooks);
        $valueIdList[] = 1234567890;

        $books = $db->checkBookLinks('goodreads', 1, 1);
        $this->assertEquals($expectedBooks, $books);

        $books = $db->checkBookLinks('goodreads', 1, null, $bookIdList);
        $this->assertEquals($expectedBooks, $books);

        $books = $db->checkBookLinks('goodreads', 1, null, null, $valueIdList);
        $this->assertEquals($expectedBooks, $books);
    }

    public function testGetTriggers(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);
        $triggers = $db->getTriggers();

        $expected = 'books_delete_trg';
        $this->assertArrayHasKey($expected, $triggers);

        $triggers = $db->getTriggers('books_series_link');

        $this->assertArrayNotHasKey($expected, $triggers);

        $expected = 'fkc_insert_books_series_link';
        $this->assertArrayHasKey($expected, $triggers);
    }
}
