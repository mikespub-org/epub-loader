<?php
/**
 * Epub loader application test
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Tests;

use Marsender\EPubLoader\Import\DataCapture;

class DataCaptureTest extends BaseTestCase
{
    public function testCaptureThis(): void
    {
        $capture = new DataCapture();
        $capture->analyze($this);
        $report = $capture->report();

        $expected = $this::class;
        $this->assertEquals($expected, $report['$comment']);
    }

    public function testCaptureSelf(): void
    {
        $capture = new DataCapture();
        $capture->analyze($capture);
        $report = $capture->report();

        $expected = $capture::class;
        $this->assertEquals($expected, $report['$comment']);
        $expected = ['structure', 'patterns'];
        $this->assertEquals($expected, array_keys($report['properties']));
    }
}
