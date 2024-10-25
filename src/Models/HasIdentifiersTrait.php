<?php
/**
 * HasIdentifiersTrait trait
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Models;

use Marsender\EPubLoader\Metadata\BaseMatch;

/**
 * Deal with authors property
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

    /**
     * Set isbn, lccn, uri etc. based on identifiers and vice-versa
     * @return self
     */
    public function fixIdentifiers()
    {
        $type = 'isbn';
        if (empty($this->isbn) && !empty($this->identifiers[$type])) {
            $this->isbn = $this->identifiers[$type]['value'];
        } elseif (!empty($this->isbn) && empty($this->identifiers[$type])) {
            $this->setIdentifier($type, $this->isbn);
        }

        $type = 'lccn';
        if (empty($this->lccn) && !empty($this->identifiers[$type])) {
            $this->lccn = $this->identifiers[$type]['value'];
        } elseif (!empty($this->lccn) && empty($this->identifiers[$type])) {
            $this->setIdentifier($type, $this->lccn);
        }

        $type = 'url';
        if (empty($this->uri) && !empty($this->identifiers[$type])) {
            $this->uri = $this->identifiers[$type]['value'];
        } elseif (!empty($this->uri) && empty($this->identifiers[$type])) {
            $this->setIdentifier($type, $this->uri);
        }

        foreach ($this->identifiers as $type => $identifier) {
            if (empty($identifier['url']) && !empty($identifier['value'])) {
                $url = BaseMatch::getTypeLink($identifier['type'], $identifier['value']);
                if (!empty($url)) {
                    $this->identifiers[$type]['url'] = $url;
                }
            }
        }
        return $this;
    }
}
