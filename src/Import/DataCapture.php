<?php
/**
 * DataCapture class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

use Exception;

class DataCapture
{
    public int $count = 0;
    /** @var array<mixed> */
    public array $structure = [];

    /**
     * Summary of analyze
     * @param object|array<mixed> $data
     * @return void
     */
    public function analyze($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        $this->count += 1;
        foreach ($data as $key => $value) {
            $this->structure[$key] ??= ['count' => []];
            $this->addKeyValue($key, $value, $this->structure[$key]);
        }
    }

    /**
     * Summary of addKeyValue
     * @param string $key
     * @param mixed $value
     * @param mixed $node
     * @return void
     */
    public function addKeyValue($key, $value, $node)
    {
        $type = gettype($value);
        if (!array_key_exists($type, $node['count'])) {
            $node['count'][$type] = 0;
        }
        $node['count'][$type] += 1;
    }

    /**
     * Summary of report
     * @return array<mixed>
     */
    public function report()
    {
        return $this->structure;
    }
}
