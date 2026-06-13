<?php

/**
 * Mapper class
 */

namespace Marsender\EPubLoader\Metadata;

class Mapper
{
    /**
     * Handle value for nullable value
     * ```
     * $data['__typename'] ?? null
     * ```
     * @param array<string, mixed> $data
     */
    public static function getValue(array $data, string $key): mixed
    {
        if (!isset($data[$key])) {
            return null;
        }
        return $data[$key];
    }

    /**
     * Handle value with callable ::fromJson() for nullable value
     * ```
     * ($data['details'] ?? null) !== null ? Details::fromJson($data['details']) : null
     * ```
     * @param array<string, mixed> $data
     */
    public static function getItem(array $data, string $key, callable $transform): mixed
    {
        if (!isset($data[$key])) {
            return null;
        }
        return $transform($data[$key]);
    }

    /**
     * Handle array map with callable ::fromJson() for nullable values
     * ```
     * ($data['bookSeries'] ?? null) !== null ? array_map(BookSeries::fromJson(...), $data['bookSeries']) : null
     * ```
     * @param array<string, mixed> $data
     * @param string $key
     * @return array<mixed>|null
     */
    public static function getArray(array $data, string $key, callable $transform): ?array
    {
        if (!isset($data[$key])) {
            return null;
        }
        return array_map($transform, $data[$key]);
    }

    /**
     * Summary of getValues
     * @param array<string, mixed> $data
     * @param array<string, null|callable|array{0: callable}> $keys
     * @return array<mixed>
     */
    public static function getValues(array $data, array $keys): array
    {
        // @todo map array key to constructor arg to use named arguments? - cfr. GoodReads\Books\BookMap
        $values = [];
        foreach ($keys as $key => $transform) {
            if (!isset($data[$key])) {
                $values[$key] = null;
                continue;
            }
            if (!isset($transform)) {
                $values[$key] = $data[$key];
                continue;
            }
            if (!is_array($transform)) {
                $values[$key] = $transform($data[$key]);
                continue;
            }
            $values[$key] = array_map($transform[0], $data[$key]);
        }
        return $values;
    }
}
