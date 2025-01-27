<?php

/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests\Storage;

use Marsender\EPubLoader\Tests\BaseTestCase;
use Google\Client;
use Google\Service\Drive;

class GoogleDriveTest extends BaseTestCase
{
    public function testGetEpubList(): void
    {
        $fileList = [];

        $expected = 0;
        $this->assertCount($expected, $fileList);
    }
}
