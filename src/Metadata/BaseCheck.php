<?php
/**
 * BaseCheck class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata;

use Marsender\EPubLoader\CalibreDbLoader;

class BaseCheck
{
    /** @var string */
    protected $cacheDir;
    /** @var string */
    protected $dbFile;
    /** @var string */
    protected $dbPath;
    /** @var BaseCache */
    protected $cache;
    /** @var BaseMatch */
    protected $match;
    /** @var CalibreDbLoader */
    protected $db;
    /** @var string */
    protected $prefix;

    /**
     * Summary of __construct
     * @param string $cacheDir
     * @param string $dbFile
     */
    public function __construct($cacheDir, $dbFile)
    {
        $this->cacheDir = $cacheDir;
        $this->dbFile = $dbFile;
        $this->dbPath = dirname($dbFile);
        $this->setProperties($cacheDir, $dbFile);
    }

    /**
     * Summary of setProperties
     * @param string $cacheDir
     * @param string $dbFile
     * @return void
     */
    public function setProperties($cacheDir, $dbFile)
    {
        $this->cache = new BaseCache($cacheDir);
        $this->match = new BaseMatch($cacheDir);
        $this->db = new CalibreDbLoader($dbFile);
        $this->prefix = '';
    }
}
