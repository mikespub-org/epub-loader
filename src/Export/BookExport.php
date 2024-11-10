<?php
/**
 * BookExport class
 */

namespace Marsender\EPubLoader\Export;

use Marsender\EPubLoader\Metadata\LocalBooks\LocalBooksTrait;

class BookExport extends SourceExport
{
    use LocalBooksTrait;
}
