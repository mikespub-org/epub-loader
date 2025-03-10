<?php
/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests\GoogleBooks;

use Marsender\EPubLoader\Metadata\GoogleBooks\GoogleBooksMatch;
use Marsender\EPubLoader\Tests\BaseTestCase;

class GoogleBooksMatchTest extends BaseTestCase
{
    public function testFindSeriesByName(): void
    {
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $match = new GoogleBooksMatch($cacheDir);
        $author = [
            'name' => 'Trudi Canavan',
        ];
        $query = 'Black Magician Trilogy';
        $series = $match->findSeriesByName($query, $author['name']);

        $expected = 'books#volumes';
        $this->assertEquals($expected, $series['kind']);
        $expected = 41;
        $this->assertEquals($expected, $series['totalItems']);
        $expected = 40;
        $this->assertCount($expected, $series['items']);
    }
}
