<?php

/**
 * NoteInfo class
 */

namespace Marsender\EPubLoader\Models;

use Marsender\EPubLoader\ActionHandler;
use Marsender\EPubLoader\CalibreDbLoader;

/**
 * SeriesInfo class contains informations about a series,
 * and methods to load this informations from multiple sources (eg epub file)
 */
class NoteInfo extends BaseInfo
{
    public string $id = '';

    public string $colname = '';

    public string $item = '';

    public string $doc = '';

    public string $html = '';

    public string $mtime = '';

    /** @var array<mixed>|null */
    public $resources = null;

    /**
     * Summary of parseHtml - see templates/models/author.html and serie.html
     * $urlPrefix = $handler->getActionUrl('resource');
     * @param string $urlPrefix {{endpoint}}/{{action}}/{{dbNum}}
     * @return string
     */
    public function parseHtml($urlPrefix)
    {
        $this->html = $this->doc;
        $this->html = str_replace('calres://', $urlPrefix . '?hash=', $this->html);
        $this->html = str_replace('?placement=', '&placement=', $this->html);
        return $this->html;
    }

    /**
     * Get list of hash => meta resources
     * @param ?CalibreDbLoader $loader if we need to get the list
     * @return array<mixed>
     */
    public function getResources($loader = null)
    {
        if (isset($this->resources)) {
            return $this->resources;
        }
        $matches = [];
        $this->resources = [];
        // <img src="calres://xxh64/7c301792c52eebf7?placement=kUxDpm6orDperFNdIqiU9A">
        if (!preg_match_all('~"calres://([^?"]*)(|\?placement=([^"]*))"~', $this->doc, $matches, PREG_SET_ORDER)) {
            return $this->resources;
        }
        foreach ($matches as $match) {
            $hash = $match[1];
            // @todo too many conversions to/from alg-digest format
            [$alg, $digest] = explode('/', $hash);
            $hash = "{$alg}-{$digest}";
            if (!empty($loader)) {
                $this->resources[$hash] = $loader->getResourceMeta($hash);
            } else {
                $this->resources[$hash] = true;
            }
        }
        return $this->resources;
    }

    /**
     * Load notes info from database
     *
     * @param string $basePath base directory
     * @param array<mixed> $data
     * @param ?CalibreDbLoader $loader if we need to load resources
     *
     * @return self|null
     */
    public static function load($basePath, $data, $loader = null)
    {
        if (empty($data)) {
            return null;
        }
        // From CalibreDbLoader::getNoteDoc():
        // item, colname, doc, mtime
        $noteInfo = new NoteInfo();
        $noteInfo->source = $data['source'] ?? 'database';
        $noteInfo->basePath = $basePath;
        $noteInfo->id = $data['id'] ?? '';
        $noteInfo->colname = $data['colname'] ?? '';
        $noteInfo->item = $data['item'] ?? '';
        $noteInfo->doc = $data['doc'] ?? '';
        $noteInfo->mtime = $data['mtime'] ?? '';
        // <img src="calres://xxh64/7c301792c52eebf7?placement=kUxDpm6orDperFNdIqiU9A">
        if (!empty($data['resources'])) {
            // ...
        }
        if (empty($loader)) {
            $noteInfo->loaded = false;
            return $noteInfo;
        }
        $noteInfo->getResources($loader);
        $noteInfo->loaded = true;
        return $noteInfo;
    }
}
