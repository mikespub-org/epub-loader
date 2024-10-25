<?php
/**
 * NoteInfo class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Models;

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

    public string $mtime = '';

    /** @var array<mixed> */
    public array $resources = [];

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
        // From CalibreDbLoader::getNotes():
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
        if (empty($loader) || empty($noteInfo->resources)) {
            return $noteInfo;
        }
        return $noteInfo;
    }
}
