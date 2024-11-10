<?php
/**
 * BookImport class
 */

namespace Marsender\EPubLoader\Import;

use Marsender\EPubLoader\Metadata\LocalBooks\LocalBooksTrait;

class BookImport extends SourceImport
{
    use LocalBooksTrait;
}
