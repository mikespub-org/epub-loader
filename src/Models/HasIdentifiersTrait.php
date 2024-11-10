<?php
/**
 * HasIdentifiersTrait trait
 */

namespace Marsender\EPubLoader\Models;

/**
 * Deal with identifiers property
 */
trait HasIdentifiersTrait
{
    /** @var array<mixed> */
    public $identifiers = [];

    /**
     * Summary of addIdentifier
     * @param mixed $type
     * @param array<mixed> $info
     * @return array<mixed>
     */
    public function addIdentifier($type, $info)
    {
        $this->identifiers[$type] = $info;
        return $info;
    }

    /**
     * Summary of setIdentifier
     * @param mixed $type
     * @param mixed $value
     * @return array<mixed>
     */
    public function setIdentifier($type, $value)
    {
        $info = [
            'type' => $type,
            'value' => $value,
        ];
        // url is set later in fixIdentifiers()
        return $this->addIdentifier($type, $info);
    }
}
