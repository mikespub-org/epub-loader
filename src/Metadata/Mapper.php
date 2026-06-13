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
     * Handle single value matching a pattern with callable ::fromJson() - see RootQuery
     * ```
     * // simulate patternProperties from JSON schema - single key here
     * // getAdsTargeting({\"getAdsTargetingInput\":{\"contextual\":{}}}) = one per book
     * $getAdsTargetingKeys = preg_grep('/^getAdsTargeting\(/', array_keys($data)) ?: [''];
     * $getAdsTargetingKey = reset($getAdsTargetingKeys);
     * // ...
     *      Mapper::getItem($data, $getAdsTargetingKey, GetAdsTargeting::fromJson(...)),
     * // ...
     * ```
     * becomes
     * ```
     *     getAdsTargeting:    Mapper::getPatternItem($data, '/^getAdsTargeting\(/', GetAdsTargeting::fromJson(...)),
     * ```
     * @param array<string, mixed> $data
     * @param string $pattern Regex pattern
     * @param callable $transform
     */
    public static function getPatternItem(array $data, string $pattern, callable $transform): mixed
    {
        $keys = preg_grep($pattern, array_keys($data)) ?: [];
        $key = reset($keys);
        if (!$key || !isset($data[$key])) {
            return null;
        }
        return $transform($data[$key]);
    }

    /**
     * Handle multiple values matching a pattern with callable ::fromJson() - see ApolloState
     * ```
     * // simulate patternProperties from JSON schema - multiple keys here
     * $contributorMap = [];
     * $contributorMapKeys = preg_grep('/^Contributor:/', array_keys($data)) ?: [];
     * foreach ($contributorMapKeys as $key) {
     *     $contributorMap[$key] = Mapper::getItem($data, $key, ContributorMap::fromJson(...));
     * }
     * ```
     * becomes
     * ```
     *     contributorMap: Mapper::getPatternMap($data, '/^Contributor:/', ContributorMap::fromJson(...)),
     * ```
     * @param array<string, mixed> $data
     * @param string $pattern Regex pattern
     * @param callable $transform
     * @return array<string, mixed>
     */
    public static function getPatternMap(array $data, string $pattern, callable $transform): array
    {
        $items = [];
        $keys = preg_grep($pattern, array_keys($data)) ?: [];
        foreach ($keys as $key) {
            $items[$key] = $transform($data[$key]);
        }
        return $items;
    }

    /**
     * Summary of getValues
     * @param array<string, mixed> $data
     * @param array<string, null|callable|array{0: callable}> $keys Keys match source JSON keys
     * @param class-string|null $class Optional class name to validate against
     * @return array<mixed>
     */
    public static function getValues(array $data, array $keys, ?string $class = null): array
    {
        $values = [];
        foreach ($keys as $sourceKey => $transform) {
            // Handle Regex Patterns to simulate patternProperties from JSON schema
            if (str_starts_with($sourceKey, '/')) {
                // ...
                //if (!is_array($transform)) {
                //    $values[$targetKey] = self::getPatternItem($data, $sourceKey, $transform);
                //} else {
                //    $targetKey .= 'Map';
                //    $values[$targetKey] = self::getPatternMap($data, $sourceKey, $transform[0]);
                //}
                //continue;
            }

            // Normalize the Source Key to determine the Constructor Argument Name
            $targetKey = self::normalizeSourceKey($sourceKey);

            if (!isset($data[$sourceKey])) {
                $values[$targetKey] = null;
                continue;
            }

            if (!isset($transform)) {
                $values[$targetKey] = $data[$sourceKey];
                continue;
            }

            if (!is_array($transform)) {
                $values[$targetKey] = $transform($data[$sourceKey]);
                continue;
            }

            $values[$targetKey] = array_map($transform[0], $data[$sourceKey]);
        }

        if ($class !== null) {
            self::validateArguments($class, $values);
        }

        return $values;
    }

    /**
     * Normalize a source JSON key to a target PHP argument name
     * @param string $sourceKey
     * @return string
     */
    public static function normalizeSourceKey(string $sourceKey): string
    {
        // Return immediately if it's already a standard camelCase key
        if ($sourceKey !== '' && !str_contains($sourceKey, '_') && !str_contains($sourceKey, '(') && !ctype_upper($sourceKey[0])) {
            return $sourceKey;
        }

        // Remove GraphQL prefixes (e.g., __typename -> typename, __ref -> ref)
        $name = ltrim($sourceKey, '_');

        // If no more underscores or complex patterns, ensure lowercase first letter
        if (!str_contains($name, '_') && !str_contains($name, '(')) {
            return lcfirst($name);
        }

        // Handle snake_case to camelCase (e.g., book_id -> bookId)
        $name = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));

        // Handle complex patterns like description({"stripped":true})
        if (str_contains($name, '(')) {
            // Replace characters like {" : with spaces to help ucwords
            $cleaned = preg_replace('/[^a-zA-Z0-9]/', ' ', $name);
            // Convert to PascalCase then back to camelCase
            $name = lcfirst(str_replace(' ', '', ucwords($cleaned)));
        }

        return $name;
    }

    /**
     * Validate that the values array contains all required parameters for the class constructor
     * @param class-string $class
     * @param array<string, mixed> $values
     * @throws \InvalidArgumentException
     */
    public static function validateArguments(string $class, array $values): void
    {
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return;
        }

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (!$parameter->isOptional()) {
                if (!array_key_exists($name, $values)) {
                    throw new \InvalidArgumentException("Missing required constructor argument: $name for class $class");
                }
                if ($values[$name] === null && !$parameter->allowsNull()) {
                    throw new \InvalidArgumentException("Argument $name for class $class cannot be null");
                }
            }
        }
    }
}
