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
}
