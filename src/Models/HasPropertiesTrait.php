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
    /** @var array<mixed> */
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
}
