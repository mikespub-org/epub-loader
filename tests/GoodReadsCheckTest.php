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
use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsCheck;
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

        $cacheDir = dirname(__DIR__) . '/cache';

        $check = new GoodReadsCheck($cacheDir, $dbFile);
        try {
            $check->checkBookLinks('goodreads');
            $result = true;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            $result = false;
        }
        $this->assertTrue($result);
    }

    #[Depends('testCheckBookLinks')]
    public function testCheckAuthorMatch(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';

        $cacheDir = dirname(__DIR__) . '/cache';

        $check = new GoodReadsCheck($cacheDir, $dbFile);
        try {
            $check->checkAuthorMatch();
            $result = true;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            $result = false;
        }
        $this->assertTrue($result);
    }

    #[Depends('testCheckBookLinks')]
    public function testCheckSeriesMatch(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';

        $cacheDir = dirname(__DIR__) . '/cache';

        $check = new GoodReadsCheck($cacheDir, $dbFile);
        try {
            $check->checkSeriesMatch();
            $result = true;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            $result = false;
        }
        $this->assertTrue($result);
    }

    #[Depends('testCheckBookLinks')]
    public function testCheckMissingMatch(): void
    {
        $dbPath = dirname(__DIR__) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';

        $cacheDir = dirname(__DIR__) . '/cache';

        $check = new GoodReadsCheck($cacheDir, $dbFile);
        try {
            $check->checkBookSeriesMatch();
            $result = true;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            $result = false;
        }
        $this->assertTrue($result);
    }
}
