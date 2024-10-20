<?php
/**
 * TestHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Handlers;

use Marsender\EPubLoader\ActionHandler;

class TestHandler extends ActionHandler
{
    /**
     * Summary of test
     * @return string
     */
    public function test()
    {
        return 'ok';
    }
}
