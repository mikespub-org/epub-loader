<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\CalibreDbLoader;
use PHPUnit\Framework\TestCase;

class CalibreDbTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!file_exists(dirname(__DIR__) . '/app/config.php')) {
            copy(dirname(__DIR__) . '/app/config.php.example', dirname(__DIR__) . '/app/config.php');
        }
        $_SERVER['SCRIPT_NAME'] = '/phpunit';
    }

    public function testGetStats(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);
        $stats = $db->getStats();

        $expected = [
            'authors' => 4,
            'books' => 7,
            'series' => 3,
        ];
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
            'last' => 'offset=2',
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
            'last' => 'sort=name&offset=6',
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
            'last' => 'sort=name&offset=2',
        ];
        $this->assertEquals($expected, $paging);
    }

    public function testGetBooksBySeries(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);
        $books = $db->getBooksBySeries(1);

        $expected = 2;
        $this->assertCount($expected, $books);
    }

    public function testCheckBookLinks(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';
        $db = new CalibreDbLoader($dbFile);

        $books = $db->checkBookLinks('goodreads');
        $expected = 7;
        $this->assertCount($expected, $books);

        $expectedBooks = [
            [
                'book' => 1,
                'value' => '102868',
                'author' => 1,
                'series' => 1,
            ],
            [
                'book' => 4,
                'value' => '4465',
                'author' => 1,
                'series' => null,
            ],
            [
                'book' => 7,
                'value' => '8921',
                'author' => 1,
                'series' => 1,
            ],
        ];

        $books = $db->checkBookLinks('goodreads', 1);
        $expected = count($expectedBooks);
        $this->assertCount($expected, $books);

        // filter books for series = 1
        $expectedBooks = array_filter($expectedBooks, function ($book) {
            return $book['series'] == 1;
        });
        $expectedBooks = array_values($expectedBooks);
        $expected = 2;
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

        $books = $db->checkBookLinks('goodreads', null, 1);
        $this->assertEquals($expectedBooks, $books);

        $books = $db->checkBookLinks('goodreads', null, null, $bookIdList);
        $this->assertEquals($expectedBooks, $books);

        $books = $db->checkBookLinks('goodreads', null, null, null, $valueIdList);
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
