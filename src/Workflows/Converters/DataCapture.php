<?php
/**
 * DataCapture class
 */

namespace Marsender\EPubLoader\Workflows\Converters;

use Marsender\EPubLoader\Models\AuthorInfo;
use Marsender\EPubLoader\Models\BookInfo;
use Marsender\EPubLoader\Models\SeriesInfo;

class DataCapture extends Converter
{
    /** @var array<mixed> */
    public array $structure = ['path' => '', 'type' => []];
    /** @var array<mixed> */
    public array $patterns = [];

    /**
     * Simulate patternProperties from JSON schema if needed (regex pattern without delimiter)
     * Example: $patterns = ['.properties' => '^P\d+$', ...];
     * @param array<string, string> $patterns [path => pattern]
     */
    public function __construct($patterns = [])
    {
        $this->patterns = [];
        // add delimiter here
        foreach ($patterns as $path => $pattern) {
            $pattern = str_replace('~', '\\~', $pattern);
            $this->patterns[$path] = '~' . $pattern . '~';
        }
    }

    /**
     * Convert info and/or id
     *
     * @param BookInfo|AuthorInfo|SeriesInfo $info object
     * @param int $id id in the calibre db (or 0 for auto incrementation)
     * @return array{0: BookInfo|AuthorInfo|SeriesInfo, 1: int}
     */
    public function convert($info, $id = 0)
    {
        $this->analyze($info);
        return [$info, $id];
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
     * @param string $path
     * @return void
     */
    public function addItem($item, &$node, $path = '')
    {
        $type = gettype($item);
        if (is_object($item)) {
            $node['$comment'] ??= $item::class;
            if (method_exists($item, 'all')) {
                $item = $item->all();
                // flatten collection here!?
                if (count($item) == 1 && !empty($item[''])) {
                    $item = $item[''];
                }
            } elseif (method_exists($item, 'toArray')) {
                $item = $item->toArray();
            } else {
                $item = get_object_vars($item);
            }
            $this->addProperties($item, $node, $path);
        } elseif ($type == 'array' && count($item) > 0) {
            $first = array_key_first($item);
            if (!is_numeric($first)) {
                $type = 'object';
                $this->addProperties($item, $node, $path);
            } else {
                $node['items'] ??= ['path' => $path . '.0', 'type' => []];
                foreach ($item as $entry) {
                    $this->addItem($entry, $node['items'], $path . '.0');
                }
            }
        } elseif (!isset($node['examples']) && !is_null($item) && !is_array($item)) {
            $node['examples'] = [ $item ];
        }
        $node['type'][$type] ??= 0;
        $node['type'][$type] += 1;
    }

    /**
     * Summary of addProperties
     * @param mixed $item
     * @param mixed $node
     * @param string $path
     * @return void
     */
    public function addProperties($item, &$node, $path)
    {
        $node['properties'] ??= [];
        $pattern = $this->patterns[$path] ?? null;
        $example = null;
        foreach ($item as $key => $val) {
            if (!empty($pattern) && preg_match($pattern, (string) $key)) {
                $example ??= $key;
                $key = $pattern;
            }
            $node['properties'][$key] ??= ['path' => $path . '.' . $key, 'type' => []];
            if (!empty($example)) {
                $node['properties'][$key]['$comment'] ??= 'Pattern example: ' . $example;
            }
            $this->addItem($val, $node['properties'][$key], $path . '.' . $key);
        }
    }

    /**
     * Summary of report
     * @param ?string $fileName
     * @return array<mixed>
     */
    public function report($fileName = null)
    {
        if (!empty($fileName)) {
            file_put_contents($fileName, json_encode($this->structure, JSON_PRETTY_PRINT));
        }
        return $this->structure;
    }
}
