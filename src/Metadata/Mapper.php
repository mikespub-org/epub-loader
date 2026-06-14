<?php

/**
 * Mapper class
 */

namespace Marsender\EPubLoader\Metadata;

/**
 * The Mapper provides a centralized engine for transforming raw JSON data into typed Metadata DTOs.
 *
 * It serves as the primary factory mechanism for the Metadata namespace, handling:
 * 1. Key Normalization: Converts GraphQL-style (__typename), snake_case (book_id), and
 *    complex parameterized keys (description({...})) into standard PHP camelCase names.
 * 2. Pattern Mapping: Simulates JSON Schema patternProperties by allowing regex keys to
 *    map dynamic JSON keys to DTO properties or collections.
 * 3. Mapping Plans: Optimizes performance by pre-calculating the relationship between
 *    source data and constructor requirements, creating a "compiled" instruction set
 *    that reduces branching and reflection overhead during bulk processing.
 * 4. Integrated Validation: Reconciles source data against PHP constructor type-hints,
 *    nullability, and optionality in a single execution pass.
 *
 * Metadata classes typically invoke the Mapper within their static fromJson() methods:
 * ```
 * return new self(...Mapper::getValues($data, $keys, self::class));
 * ```
 */
class Mapper
{
    public const VALIDATE_TYPES = false;

    /** @var array<string, array<string, array{name: string, isOptional: bool, allowsNull: bool, type: ?string}>> */
    private static array $constructorCache = [];

    /** @var array<string, string> */
    private static array $normalizationCache = [];

    /** @var array<string, array<int, array{source: string, target: string, transform: mixed, mode: int<0, 2>, is_pattern: bool, validation: ?array{type: ?string, allowsNull: bool, isOptional: bool}}>> */
    private static array $mappingPlans = [];

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

        // 1. Retrieve or build the Mapping Plan
        $plan = self::getMappingPlan($keys, $class);

        // 2. Execute the Mapping Plan
        foreach ($plan as $step) {
            $target = $step['target'];
            $source = $step['source'];
            $transform = $step['transform'];
            $value = null;

            // A. Extraction & Transformation
            if ($step['is_pattern']) {
                if ($step['mode'] === 2) {
                    $value = self::getPatternMap($data, $source, $transform[0]);
                } else {
                    $value = self::getPatternItem($data, $source, $transform);
                }
            } elseif (isset($data[$source])) {
                $value = match ($step['mode']) {
                    0 => $data[$source],
                    1 => $transform($data[$source]),
                    2 => array_map($transform[0], $data[$source]),
                };
            }

            // B. Integrated Validation
            if (self::VALIDATE_TYPES && $step['validation']) {
                $v = $step['validation'];
                if ($value === null && !$v['allowsNull'] && !$v['isOptional']) {
                    throw new \InvalidArgumentException("Argument $target for class $class cannot be null");
                }
                if ($value !== null && $v['type']) {
                    $type = $v['type'];
                    $isValid = match ($type) {
                        'int'    => is_int($value),
                        'string' => is_string($value),
                        'bool'   => is_bool($value),
                        'float'  => is_float($value) || is_int($value),
                        'array'  => is_array($value),
                        'mixed'  => true,
                        default  => $value instanceof $type,
                    };
                    if (!$isValid) {
                        throw new \InvalidArgumentException("Argument $target for class $class must be of type $type, " . get_debug_type($value) . " given");
                    }
                }
            }

            $values[$target] = $value;
        }

        return $values;
    }

    /**
     * Retrieve or build the Mapping Plan for a set of keys and optional class
     * @param array<string, null|callable|array{0: callable}> $keys
     * @param class-string|null $class
     * @return array<int, array{source: string, target: string, transform: mixed, mode: int<0, 2>, is_pattern: bool, validation: ?array{type: ?string, allowsNull: bool, isOptional: bool}}>
     */
    private static function getMappingPlan(array $keys, ?string $class = null): array
    {
        if ($class !== null && isset(self::$mappingPlans[$class])) {
            return self::$mappingPlans[$class];
        }

        $constructorParams = $class ? self::getConstructorParams($class) : [];
        $plan = [];
        $mappedTargets = [];

        foreach ($keys as $sourceKey => $transform) {
            $isPattern = str_starts_with($sourceKey, '/');
            $targetKey = self::normalizeSourceKey($sourceKey);
            if ($isPattern && is_array($transform)) {
                // See GoodReads\Books\ApolloState
                $targetKey .= 'Map';
            }

            // mode: 0 = raw, 1 = transform, 2 = array map
            $mode = ($transform === null) ? 0 : (is_array($transform) ? 2 : 1);
            $validation = $constructorParams[$targetKey] ?? null;

            $plan[] = [
                'source'     => $sourceKey,
                'target'     => $targetKey,
                'transform'  => $transform,
                'mode'       => $mode,
                'is_pattern' => $isPattern,
                'validation' => $validation,
            ];

            if ($validation) {
                $mappedTargets[$targetKey] = true;
            }
        }

        // Reconciliation: Ensure all required constructor parameters are covered by the keys mapping
        foreach ($constructorParams as $name => $param) {
            if (!$param['isOptional'] && !isset($mappedTargets[$name])) {
                throw new \RuntimeException("Constructor parameter '$name' for class '$class' is required but missing from mapping.");
            }
        }

        if ($class !== null) {
            self::$mappingPlans[$class] = $plan;
        }

        return $plan;
    }

    /**
     * Normalize a source JSON key to a target PHP argument name
     * @param string $sourceKey
     * @return string
     */
    public static function normalizeSourceKey(string $sourceKey): string
    {
        if (isset(self::$normalizationCache[$sourceKey])) {
            return self::$normalizationCache[$sourceKey];
        }

        // Handle regex pattern as source key
        if (str_starts_with($sourceKey, '/')) {
            $name = preg_replace('/[\W]+/', '', $sourceKey);
            return self::$normalizationCache[$sourceKey] = lcfirst($name);
        }

        // Return immediately if it's already a standard camelCase key
        if ($sourceKey !== '' && !str_contains($sourceKey, '_') && !str_contains($sourceKey, '(') && !ctype_upper($sourceKey[0])) {
            return $sourceKey;
        }

        // Remove GraphQL prefixes (e.g., __typename -> typename, __ref -> ref)
        $name = ltrim($sourceKey, '_');

        // If no more underscores or complex patterns, ensure lowercase first letter
        if (!str_contains($name, '_') && !str_contains($name, '(')) {
            return self::$normalizationCache[$sourceKey] = ctype_upper($name) ? strtolower($name) : lcfirst($name);
        }

        // Handle snake_case to camelCase (e.g., book_id -> bookId, ROOT_QUERY -> rootQuery)
        $name = lcfirst(str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $name)))));

        // Handle complex patterns like description({"stripped":true})
        if (str_contains($name, '(')) {
            // Replace characters like {" : with spaces to help ucwords
            $cleaned = preg_replace('/[^a-zA-Z0-9]/', ' ', $name);
            // Convert to PascalCase then back to camelCase
            $name = lcfirst(str_replace(' ', '', ucwords($cleaned)));
        }

        return self::$normalizationCache[$sourceKey] = $name;
    }

    /**
     * Validate that the values array contains all required parameters for the class constructor
     * @deprecated Logic moved to integrated loop in getValues()
     * @param class-string $class
     * @param array<string, mixed> $values
     * @throws \InvalidArgumentException
     */
    public static function validateArguments(string $class, array $values): void
    {
        self::getConstructorParams($class);
    }

    /**
     * @param class-string $class
     * @return array<string, array{name: string, isOptional: bool, allowsNull: bool, type: ?string}>
     */
    private static function getConstructorParams(string $class): array
    {
        if (isset(self::$constructorCache[$class])) {
            return self::$constructorCache[$class];
        }

        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return self::$constructorCache[$class] = [];
        }

        $params = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            $typeName = ($type instanceof \ReflectionNamedType) ? $type->getName() : null;
            $params[$parameter->getName()] = [
                'name'       => $parameter->getName(),
                'isOptional' => $parameter->isOptional(),
                'allowsNull' => $parameter->allowsNull(),
                'type'       => $typeName,
            ];
        }

        return self::$constructorCache[$class] = $params;
    }
}
