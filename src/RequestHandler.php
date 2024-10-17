<?php
/**
 * RequestHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

use Marsender\EPubLoader\Metadata\BaseCache;
use Exception;

class RequestHandler
{
    public const ENDPOINT = './index.php';
    public const APP_NAME = 'EPub Loader';
    public const VERSION = '3.3';
    public const TEMPLATE = 'index.html';

    /** @var array<mixed> */
    public $gConfig = [];
    /** @var string */
    public $handlerClass;
    /** @var string|null */
    public $cacheDir;
    /** @var string|null */
    public $templateDir;
    /** @var string */
    public $template;
    /** @var array<mixed> */
    protected $gErrorArray;
    /** @var array<mixed> */
    protected $urlParams;
    /** @var string|null */
    protected $urlPath;
    /** @var bool */
    protected $handled = false;

    /**
     * Summary of __construct
     * @param array<mixed> $gConfig
     * @param string $handlerClass
     * @param string|null $cacheDir
     */
    public function __construct($gConfig, $handlerClass = ActionHandler::class, $cacheDir = null)
    {
        $this->loadConfig($gConfig);
        $this->gErrorArray = [];
        $this->handlerClass = $handlerClass;
        $this->cacheDir = $cacheDir;
        $this->templateDir = dirname(__DIR__) . '/templates';
        $this->template = static::TEMPLATE;
    }

    /**
     * Summary of loadConfig
     * @param array<mixed> $config
     * @return void
     */
    public function loadConfig($config)
    {
        $this->gConfig = array_merge($this->gConfig, $config);
        // verify required keys
        $this->gConfig['create_db'] ??= false;
        $this->gConfig['databases'] ??= [];
        $this->gConfig['actions'] ??= [];
        // use action groups for display
        $this->gConfig['groups'] ??= [];
        foreach ($this->gConfig['groups'] as $group => $actionList) {
            foreach ($actionList as $action => $actionInfo) {
                $this->gConfig['actions'][$action] ??= $actionInfo;
            }
        }
        // verify expected keys
        $this->gConfig['endpoint'] ??= static::ENDPOINT;
        $this->gConfig['app_name'] ??= static::APP_NAME;
        $this->gConfig['version'] ??= static::VERSION;
    }

    /**
     * Summary of request
     * @param string|null $action
     * @param string|int|null $dbNum
     * @param array<mixed> $urlParams
     * @param string|null $urlPath after /action/dbNum/authorId
     * @return array<mixed>|string|null
     */
    public function request($action = null, $dbNum = null, $urlParams = [], $urlPath = null)
    {
        $this->urlParams = $urlParams;
        $this->urlPath = $urlPath;
        if (isset($action) && isset($dbNum)) {
            // Get result of the action
            return $this->getAction($action, $dbNum);
        }
        if (!isset($action)) {
            // Get available action groups
            return $this->getActionGroups($action, $dbNum);
        }
        if (!array_key_exists($action, $this->gConfig['actions'])) {
            // Display invalid action
            return $this->getInvalidAction($action, $dbNum);
        }
        // Display databases
        return $this->getDatabases($action, $dbNum);
    }

    /**
     * Summary of get
     * @param string $name
     * @param mixed $default
     * @param ?string $pattern
     * @return mixed
     */
    public function get($name, $default = null, $pattern = null)
    {
        if (!empty($this->urlParams) && isset($this->urlParams[$name]) && $this->urlParams[$name] != '') {
            if (!isset($pattern) || preg_match($pattern, (string) $this->urlParams[$name])) {
                return $this->urlParams[$name];
            }
        }
        return $default;
    }

    /**
     * Summary of getId
     * @param string $name
     * @return ?int
     */
    public function getId($name)
    {
        $value = $this->get($name, null, '/^\d+$/');
        if (!is_null($value)) {
            return (int) $value;
        }
        return null;
    }

    /**
     * Summary of set
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name, $value = null)
    {
        if (is_null($value)) {
            unset($this->urlParams[$name]);
            return;
        }
        $this->urlParams[$name] = $value;
    }

    /**
     * Summary of getPath
     * @return string
     */
    public function getPath()
    {
        return trim($this->urlPath ?? '', '/');
    }

    /**
     * Summary of getEndpoint
     * @return string
     */
    public function getEndpoint()
    {
        return $this->gConfig['endpoint'];
    }

    /**
     * Summary of getActionGroups
     * @param string|null $action
     * @param string|int|null $dbNum
     * @return array<mixed>|string|null
     */
    protected function getActionGroups($action = null, $dbNum = null)
    {
        $result = [];
        $result['action'] = $action;
        $result['dbNum'] = $dbNum;
        $result['groups'] = $this->gConfig['groups'] ?? [];
        $result['actions'] = $this->gConfig['actions'];
        $result['databases'] = $this->gConfig['databases'];
        $result['errors'] = $this->getErrors();
        $this->template = 'actions.html';
        return $result;
    }

    /**
     * Summary of getInvalidAction
     * @param string $action
     * @param string|int|null $dbNum
     * @return array<mixed>|string|null
     */
    protected function getInvalidAction($action, $dbNum = null)
    {
        $this->gErrorArray[$action] = 'Invalid action';
        $result = [];
        $result['action'] = $action;
        $result['dbNum'] = $dbNum;
        $result['actions'] = $this->gConfig['actions'];
        $result['databases'] = $this->gConfig['databases'];
        $result['errors'] = $this->getErrors();
        return $result;
    }

    /**
     * Summary of getDatabases
     * @param string $action
     * @param string|int|null $dbNum
     * @return array<mixed>|string|null
     */
    protected function getDatabases($action, $dbNum = null)
    {
        $result = [];
        $result['action'] = $action;
        $result['dbNum'] = $dbNum;
        $result['actionTitle'] = $this->gConfig['actions'][$action];
        $result['actions'] = $this->gConfig['actions'];
        $result['databases'] = $this->gConfig['databases'];
        $result = $this->getDatabaseStats($action, $result);
        $result['errors'] = $this->getErrors();
        $this->template = 'databases.html';
        return $result;
    }

    /**
     * Summary of getDatabaseStats
     * @param string $action
     * @param array<mixed> $result
     * @return array<mixed>
     */
    protected function getDatabaseStats($action, $result)
    {
        $cacheFile = null;
        $saveFile = false;
        $sizes = [];
        $result['statsUpdated'] = 'never';
        if (!empty($this->cacheDir)) {
            // cache database stats and file counts for 2 hours
            $cacheFile = $this->cacheDir . '/sizes.json';
            $refresh = $this->get('refresh');
            if (empty($refresh) && file_exists($cacheFile) && filemtime($cacheFile) > time() - 2 * 60 * 60) {
                $content = file_get_contents($cacheFile);
                $sizes = json_decode($content, true);
                $result['statsUpdated'] = (string) intval((time() - filemtime($cacheFile)) / 60);
                $result['statsUpdated'] .= ' minutes ago';
            }
        }
        foreach ($this->gConfig['databases'] as $dbNum => $dbConfig) {
            $dbPath = $dbConfig['db_path'];
            if (!empty($sizes[$dbPath])) {
                $result['databases'][$dbNum] = array_merge($result['databases'][$dbNum], $sizes[$dbPath]);
                continue;
            }
            $saveFile = true;
            $dbFileName = $dbPath . DIRECTORY_SEPARATOR . 'metadata.db';
            // Open the database
            if (is_file($dbFileName)) {
                $db = new CalibreDbLoader($dbFileName);
                $sizes[$dbPath] = $db->getStats();
            } else {
                $sizes[$dbPath] = [
                    'authors' => 0,
                    'books' => 0,
                    'series' => 0,
                ];
            }
            $epubPath = $dbConfig['epub_path'];
            if ($action == 'csv_import') {
                $fileList = BaseCache::getFiles($dbPath, '*.csv');
            } elseif ($action == 'json_import') {
                $fileList = BaseCache::getFiles($dbPath . DIRECTORY_SEPARATOR . $epubPath, '*.json');
            } else {
                $fileList = BaseCache::getFiles($dbPath . DIRECTORY_SEPARATOR . $epubPath, '*.epub');
            }
            $sizes[$dbPath]['count'] = count($fileList);
            $result['databases'][$dbNum] = array_merge($result['databases'][$dbNum], $sizes[$dbPath]);
        }
        if (!empty($cacheFile) && $saveFile) {
            file_put_contents($cacheFile, json_encode($sizes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $result['statsUpdated'] = 'now';
        }
        return $result;
    }

    /**
     * Summary of getAction
     * @param string $action
     * @param string|int $dbNum
     * @return array<mixed>|string|null
     */
    protected function getAction($action, $dbNum)
    {
        $result = $this->doAction($action, $dbNum);
        if (is_null($result) && empty($this->getErrors()) && !empty(getenv('PHPUNIT_TESTING'))) {
            $this->handled = true;
            return $result;
        }
        if (!is_array($result)) {
            $result = ['result' => $result];
        }
        $result['databases'] = $this->gConfig['databases'];
        if ($action == 'caches') {
            $result = $this->getDatabaseStats($action, $result);
        }
        $result['action'] = $action;
        $result['actionTitle'] = $this->gConfig['actions'][$action];
        $result['dbNum'] = $dbNum;
        $result['dbConfig'] = $result['databases'][$dbNum];
        $result['actions'] = $this->gConfig['actions'];
        $result['errors'] = $this->getErrors();
        // check if the template file actually exists in output()
        $this->template = $action . '.html';
        return $result;
    }

    /**
     * Summary of doAction
     * @param string $action
     * @param string|int $dbNum
     * @return array<mixed>|string|null
     */
    protected function doAction($action, $dbNum)
    {
        $result = null;
        if (!array_key_exists($action, $this->gConfig['actions'])) {
            $this->gErrorArray[$action] = 'Invalid action';
            return null;
        }
        if (!isset($this->gConfig['databases'][$dbNum])) {
            $this->gErrorArray[$dbNum] = 'Incorrect database num: ' . $dbNum;
            return null;
        }
        $dbConfig = $this->gConfig['databases'][$dbNum];
        $dbPath = $dbConfig['db_path'];
        if (!is_dir($dbPath)) {
            if (!mkdir($dbPath, 0o755, true)) {
                $this->gErrorArray[$dbPath] = 'Cannot create directory: ' . $dbPath;
                return null;
            }
        }
        // remove support for single action files in app directory (app/action_<something>.php)
        if (!$this->handlerClass::hasAction($action)) {
            $this->gErrorArray[$action] = 'Incorrect action ' . $action . ' in class ' . $this->handlerClass;
            return null;
        }
        $dbConfig['db_num'] = $dbNum;
        $dbConfig['create_db'] = $this->gConfig['create_db'];
        /** @var ActionHandler $handler */
        $handler = new $this->handlerClass($dbConfig, $this->cacheDir);
        try {
            $result = $handler->handle($action, $this);
        } catch (Exception $e) {
            $handler->addError($dbPath, $e->getMessage());
            $result = null;
        }
        $this->gErrorArray = $handler->getErrors();
        return $result;
    }

    /**
     * Summary of getErrors
     * @return array<mixed>
     */
    public function getErrors()
    {
        return $this->gErrorArray;
    }

    /**
     * Summary of isDone
     * @return bool
     */
    public function isDone()
    {
        return $this->handled && empty($this->gErrorArray);
    }

    /**
     * Summary of output
     * @param array<mixed> $result
     * @param string|null $templateDir
     * @param string|null $template
     * @return string
     */
    public function output($result, $templateDir = null, $template = null)
    {
        $templateDir ??= $this->templateDir;
        $loader = new \Twig\Loader\FilesystemLoader($templateDir);
        $twig = new \Twig\Environment($loader, [
            'debug' => true,
        ]);
        $twig->addExtension(new \Twig\Extension\DebugExtension());

        $template ??= $this->template;
        // check if the template file actually exists under $templateDir here
        if (!is_file($templateDir . '/' . $template)) {
            $template = static::TEMPLATE;
        }

        return $twig->render($template, $result);
    }
}
