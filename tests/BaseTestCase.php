<?php

/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!file_exists(dirname(__DIR__) . '/app/config.php')) {
            copy(dirname(__DIR__) . '/app/config.php.example', dirname(__DIR__) . '/app/config.php');
        }
        $_SERVER['SCRIPT_NAME'] = '/phpunit';
    }
}
