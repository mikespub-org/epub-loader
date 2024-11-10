<?php
/**
 * Epub loader application test
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\App\ExtraActions;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ExtraActions::class)]
class ExtraActionsTest extends BaseTestCase
{
    /**
     * Summary of testAppHelloWorld
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppHelloWorld(): void
    {
        $_SERVER['PATH_INFO'] = '/hello_world/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/hello_world">Example: Hello, World - see app/example.php</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Hello, World! for database Some Books';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppGoodbye
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGoodbye(): void
    {
        $_SERVER['PATH_INFO'] = '/goodbye/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<th colspan="2">Errors (1)</th>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Why leave so soon?';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    public function testGetActions(): void
    {
        $actions = ExtraActions::getActions();

        $expected = ['hello_world', 'goodbye'];
        $this->assertEquals($expected, $actions);
    }
}
