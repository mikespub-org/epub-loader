<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

class CalibreNotesTest extends BaseTestCase
{
    /**
     * Summary of testAppListNotesTypes
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppListNotesTypes(): void
    {
        $_SERVER['PATH_INFO'] = '/notes/0';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/notes/0?colName=authors">Authors</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Notes';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
    }

    /**
     * Summary of testAppListAuthorNotes
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppListAuthorNotes(): void
    {
        $_SERVER['PATH_INFO'] = '/notes/0';
        $_GET['colName'] = 'authors';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/notes/0?colName=authors&itemId=3">3</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = 'Authors Notes';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['colName']);
    }

    /**
     * Summary of testAppGetAuthorNote
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetAuthorNote(): void
    {
        $_SERVER['PATH_INFO'] = '/notes/0';
        $_GET['colName'] = 'authors';
        $_GET['itemId'] = '3';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/notes/0?colName=authors&itemId=3&html=1">html</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '&lt;p&gt;This is a &lt;strong&gt;note&lt;/strong&gt; for Lewis Carroll&lt;/p&gt;';
        $this->assertStringContainsString($expected, $output);
        $expected = '&lt;img src=&quot;calres://xxh64/7c301792c52eebf7?placement=kUxDpm6orDperFNdIqiU9A&quot;&gt;';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['colName']);
        unset($_GET['itemId']);
    }

    /**
     * Summary of testAppGetAuthorNoteHtml
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetAuthorNoteHtml(): void
    {
        $_SERVER['PATH_INFO'] = '/notes/0';
        $_GET['colName'] = 'authors';
        $_GET['itemId'] = '3';
        $_GET['html'] = '1';

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = '<title>EPub Loader</title>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<a href="/phpunit/notes/0?colName=authors&itemId=3">3</a>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<p>This is a <strong>note</strong> for Lewis Carroll</p>';
        $this->assertStringContainsString($expected, $output);
        $expected = '<img src="/phpunit/resource/0?hash=xxh64/7c301792c52eebf7&placement=kUxDpm6orDperFNdIqiU9A">';
        $this->assertStringContainsString($expected, $output);

        unset($_SERVER['PATH_INFO']);
        unset($_GET['colName']);
        unset($_GET['itemId']);
        unset($_GET['html']);
    }

    /**
     * Summary of testAppGetResource
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppGetResource(): void
    {
        $_SERVER['PATH_INFO'] = '/resource/0';
        $_GET['hash'] = 'xxh64/7c301792c52eebf7';
        $_GET['placement'] = 'kUxDpm6orDperFNdIqiU9A';
        putenv('PHPUNIT_TESTING=1');

        ob_start();
        $headers = headers_list();
        require dirname(__DIR__) . '/app/index.php';
        $output = ob_get_clean();

        $expected = 37341;
        $this->assertEquals($expected, strlen($output));

        unset($_SERVER['PATH_INFO']);
        unset($_GET['hash']);
        unset($_GET['placement']);
        putenv('PHPUNIT_TESTING=');
    }
}
