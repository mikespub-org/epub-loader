<?php
/**
 * BookImport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\LocalBooks\LocalBooksTrait;

class BookImport extends SourceImport
{
    use LocalBooksTrait;
}
