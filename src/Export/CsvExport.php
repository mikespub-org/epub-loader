<?php
/**
 * CsvExport class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Export;

class CsvExport extends BaseExport
{
    /** @var array<string>|null */
    protected $mLines = null;

    public const CsvSeparator = "\t";

    /**
     * Open an export file (or create if file does not exist)
     *
     * @param string $inFileName Export file name
     * @param boolean $inCreate Force file creation
     */
    public function __construct($inFileName, $inCreate = false)
    {
        $this->mSearch = ["\r", "\n", static::CsvSeparator];
        $this->mReplace = ['', '<br />', ''];

        // Init container
        $this->mLines = [];

        parent::__construct($inFileName, $inCreate);
    }

    /**
     * Add the current properties into the export content
     * and reset the properties
     * @return void
     */
    public function addContent()
    {
        $text = '';
        foreach ($this->mProperties as $key => $value) {
            $info = '';
            if (is_array($value)) {
                foreach ($value as $value1) {
                    // Escape quotes
                    if (str_contains((string) $value1, '\'')) {
                        $value1 = '\'' . str_replace('\'', '\'\'', $value1) . '\'';
                    }
                    $text .= $value1 . static::CsvSeparator;
                }
                continue;
            } else {
                // Escape quotes
                if (str_contains((string) $value, '\'')) {
                    $value = '\'' . str_replace('\'', '\'\'', $value) . '\'';
                }
                $info = $value;
            }
            $text .= $info . static::CsvSeparator;
        }

        $this->mLines[] = $text;

        $this->clearProperties();
    }

    /**
     * Summary of GetContent
     * @return string
     */
    protected function getContent()
    {
        $text = implode("\n", $this->mLines) . "\n";

        return $text;
    }
}
