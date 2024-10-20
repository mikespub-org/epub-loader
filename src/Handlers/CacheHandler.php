<?php
/**
 * CacheHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Handlers;

use Marsender\EPubLoader\ActionHandler;
use Marsender\EPubLoader\CalibreDbLoader;
use Marsender\EPubLoader\Metadata\BaseCache;

class CacheHandler extends ActionHandler
{
    /**
     * Summary of caches
     * @return array<mixed>
     */
    public function caches()
    {
        $result = [];
        if (empty($this->cacheDir)) {
            return $result;
        }
        // use last part of /action/dbNum/authorId here
        $result['cacheName'] = $this->request->get('authorId');
        $result['cacheType'] = $this->request->getPath();
        $result['cacheUpdated'] = 'never';
        // cache file counts for 2 hours
        $cacheFile = $this->cacheDir . '/caches.json';
        $refresh = $this->request->get('refresh');
        if (empty($refresh) && file_exists($cacheFile) && filemtime($cacheFile) > time() - 2 * 60 * 60) {
            $content = file_get_contents($cacheFile);
            $result['caches'] = json_decode($content, true);
            $result['cacheUpdated'] = (string) intval((time() - filemtime($cacheFile)) / 60);
            $result['cacheUpdated'] .= ' minutes ago';
        } else {
            $result['caches'] = BaseCache::getCacheStats($this->cacheDir);
            file_put_contents($cacheFile, json_encode($result['caches'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $result['cacheUpdated'] = 'now';
        }
        if (empty($result['cacheType'])) {
            return $result;
        }
        // get entries for cacheType
        $result['cacheEntry'] = $this->request->get('entry');
        if (!empty($result['cacheEntry'])) {
            $entry = BaseCache::getCacheEntry($this->cacheDir, $result['cacheName'], $result['cacheType'], $result['cacheEntry']);
            $result['entry'] = json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return $result;
        }
        $offset = $this->request->getId('offset');
        $result['entries'] = BaseCache::getCacheEntries($this->cacheDir, $result['cacheName'], $result['cacheType'], $offset);
        $result['paging'] = null;
        foreach ($result['caches'] as $cache => $count) {
            if (strtolower($cache) != $result['cacheName']) {
                continue;
            }
            if (!empty($count[$result['cacheType']]) && $count[$result['cacheType']] > BaseCache::$limit) {
                $result['paging'] = CalibreDbLoader::getCountPaging($count[$result['cacheType']], null, $offset, BaseCache::$limit);
                $result['paging']['itemId'] = $result['cacheName'] . '/' . $result['cacheType'];
            }
            break;
        }
        return $result;
    }
}
