<?php
/**
 * DataCapture class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Import;

class DataCapture
{
    /** @var array<mixed> */
    public array $structure = ['path' => '', 'type' => []];
    /** @var array<mixed> */
    public array $patterns = [];

    /**
     * Simulate patternProperties from JSON schema if needed
     * Example: $patterns = ['.properties' => '/^P\d+$/', ...];
     * @param array<string, string> $patterns [path => pattern]
     */
    public function __construct($patterns = [])
    {
        $this->patterns = $patterns;
    }

    /**
     * Summary of analyze
     * @param object|array<mixed> $data
     * @return void
     */
    public function analyze($data)
    {
        $this->addItem($data, $this->structure);
    }

    /**
     * Summary of addItem
     * @param mixed $item
     * @param mixed $node
     * @param mixed $path
     * @return void
     */
    public function addItem($item, &$node, $path = '')
    {
        $type = gettype($item);
        if (is_object($item)) {
            if (method_exists($item, 'toArray')) {
                // @todo support Collection like WikiData Entity
                $item = $item->toArray();
            } else {
                $item = get_object_vars($item);
            }
            $this->addProperties($item, $node, $path);
        } elseif ($type == 'array' && count($item) > 0) {
            $first = array_keys($item)[0];
            if (!is_numeric($first)) {
                $type = 'object';
                $this->addProperties($item, $node, $path);
            } else {
                $node['items'] ??= ['path' => $path . '.0', 'type' => []];
                foreach ($item as $entry) {
                    $this->addItem($entry, $node['items'], $path . '.0');
                }
            }
        }
        $node['type'][$type] ??= 0;
        $node['type'][$type] += 1;
    }

    /**
     * Summary of addProperties
     * @param mixed $item
     * @param mixed $node
     * @return void
     */
    public function addProperties($item, &$node, $path)
    {
        $node['properties'] ??= [];
        $pattern = $this->patterns[$path] ?? null;
        foreach ($item as $key => $val) {
            if (!empty($pattern) && preg_match($pattern, $key)) {
                $key = $pattern;
            }
            $node['properties'][$key] ??= ['path' => $path . '.' . $key, 'type' => []];
            $this->addItem($val, $node['properties'][$key], $path . '.' . $key);
        }
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
