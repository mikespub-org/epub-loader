<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests\GoodReads;

use Marsender\EPubLoader\Metadata\GoodReads\GoodReadsCheck;
use Marsender\EPubLoader\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\Depends;
use Exception;

class GoodReadsCheckTest extends BaseTestCase
{
    public function testCheckBookLinks(): void
    {
        $dbPath = dirname(__DIR__, 2) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';

        $cacheDir = dirname(__DIR__, 2) . '/cache';

        $check = new GoodReadsCheck($cacheDir, $dbFile);
        try {
            $check->checkBookLinks('goodreads');
            $result = true;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            $result = false;
        }
        $errors = $check->getErrors();
        if (!empty($errors)) {
            echo json_encode($errors, JSON_PRETTY_PRINT) . "\n";
        }
        $this->assertTrue($result);
    }

    #[Depends('testCheckBookLinks')]
    public function testCheckAuthorMatch(): void
    {
        $dbPath = dirname(__DIR__, 2) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';

        $cacheDir = dirname(__DIR__, 2) . '/cache';

        $check = new GoodReadsCheck($cacheDir, $dbFile);
        try {
            $check->checkAuthorMatch();
            $result = true;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            $result = false;
        }
        $errors = $check->getErrors();
        if (!empty($errors)) {
            echo json_encode($errors, JSON_PRETTY_PRINT) . "\n";
        }
        $this->assertTrue($result);
    }

    #[Depends('testCheckBookLinks')]
    public function testCheckSeriesMatch(): void
    {
        $dbPath = dirname(__DIR__, 2) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';

        $cacheDir = dirname(__DIR__, 2) . '/cache';

        $check = new GoodReadsCheck($cacheDir, $dbFile);
        try {
            $check->checkSeriesMatch();
            $result = true;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            $result = false;
        }
        $errors = $check->getErrors();
        if (!empty($errors)) {
            echo json_encode($errors, JSON_PRETTY_PRINT) . "\n";
        }
        $this->assertTrue($result);
    }

    #[Depends('testCheckBookLinks')]
    public function testCheckMissingMatch(): void
    {
        $dbPath = dirname(__DIR__, 2) . '/cache/goodreads';
        $dbFile = $dbPath . '/metadata.db';

        $cacheDir = dirname(__DIR__, 2) . '/cache';

        $check = new GoodReadsCheck($cacheDir, $dbFile);
        try {
            $check->checkBookSeriesMatch();
            $result = true;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            $result = false;
        }
        $errors = $check->getErrors();
        if (!empty($errors)) {
            echo json_encode($errors, JSON_PRETTY_PRINT) . "\n";
        }
        $this->assertTrue($result);
    }
}
