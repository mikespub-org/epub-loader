<?php

/**
 * HasPropertiesTrait trait
 */

namespace Marsender\EPubLoader\Models;

/**
 * Deal with misc. properties
 */
trait HasPropertiesTrait
{
    /** @var array<mixed>|null */
    public $properties = [];

    /**
     * Summary of addProperty
     * @param mixed $key
     * @param mixed $info
     * @return mixed
     */
    public function addProperty($key, $info)
    {
        $this->properties[$key] = $info;
        return $info;
    }

    /**
     * Summary of filterProperties
     * @param mixed $object
     * @return array<mixed>|null
     */
    public static function filterProperties($object)
    {
        if (is_object($object)) {
            $object = (array) $object;
        }
        $result = [];
        foreach ($object as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $value = static::filterProperties($value);
            }
            if (!is_null($value)) {
                $result[$key] = $value;
            }
        }
        if (empty($result)) {
            return null;
        }
        return $result;
    }
}
