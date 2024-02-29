<?php
/**
 * RequestHandler class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

use Exception;

class RequestHandler
{
    public const ENDPOINT = './index.php';
    public const APP_NAME = 'Epub Loader';
    public const VERSION = '2.3';
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
     * @return array<mixed>|string|null
     */
    public function request($action = null, $dbNum = null, $urlParams = [])
    {
        $this->urlParams = $urlParams;
        $result = null;
        // Html content
        if (isset($action) && isset($dbNum)) {
            $result = $this->doAction($action, $dbNum);
            if (!is_array($result)) {
                $result = ['result' => $result];
            }
            $result['action'] = $action;
            $result['actionTitle'] = $this->gConfig['actions'][$action];
            $result['dbNum'] = $dbNum;
            $result['dbConfig'] = $this->gConfig['databases'][$dbNum];
            $result['actions'] = $this->gConfig['actions'];
            $result['databases'] = $this->gConfig['databases'];
            $result['errors'] = $this->getErrors();
            // check if the template file actually exists in output()
            $this->template = $action . '.html';
            return $result;
        }
        if (!isset($action)) {
            // Display the available actions
            $result = [];
            $result['action'] = $action;
            $result['dbNum'] = $dbNum;
            $result['actions'] = $this->gConfig['actions'];
            $result['databases'] = $this->gConfig['databases'];
            $result['errors'] = $this->getErrors();
            $this->template = 'actions.html';
            return $result;
        }
        if (!array_key_exists($action, $this->gConfig['actions'])) {
            $this->gErrorArray[$action] = 'Invalid action';
            $result = [];
            $result['action'] = $action;
            $result['dbNum'] = $dbNum;
            $result['actions'] = $this->gConfig['actions'];
            $result['databases'] = $this->gConfig['databases'];
            $result['errors'] = $this->getErrors();
            return $result;
        }
        // Display databases
        $result = [];
        $result['action'] = $action;
        $result['dbNum'] = $dbNum;
        $result['actionTitle'] = $this->gConfig['actions'][$action];
        $result['actions'] = $this->gConfig['actions'];
        $result['databases'] = $this->gConfig['databases'];
        foreach ($this->gConfig['databases'] as $dbNum => $dbConfig) {
            $dbPath = $dbConfig['db_path'];
            $epubPath = $dbConfig['epub_path'];
            $fileList = static::getFiles($dbPath . DIRECTORY_SEPARATOR . $epubPath, '*.epub');
            $result['databases'][$dbNum]['count'] = count($fileList);
        }
        $result['errors'] = $this->getErrors();
        $this->template = 'databases.html';
        return $result;
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
            if (!isset($pattern) || preg_match($pattern, $this->urlParams[$name])) {
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
        // @todo remove this in the future
        if (!$this->handlerClass::hasAction($action)) {
            $fileName = sprintf('%s%s%s%saction_%s.php', dirname(__DIR__), DIRECTORY_SEPARATOR, 'app', DIRECTORY_SEPARATOR, $action);
            if (!file_exists($fileName)) {
                $this->gErrorArray[$fileName] = 'Incorrect action file: ' . $fileName;
                return null;
            }
            $result = require($fileName);
            return $result;
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
        $twig = new \Twig\Environment($loader);

        $template ??= $this->template;
        // check if the template file actually exists under $templateDir here
        if (!is_file($templateDir . '/' . $template)) {
            $template = static::TEMPLATE;
        }

        return $twig->render($template, $result);
    }

    /**
     * Recursive get files
     *
     * @param string $inPath Base directory to search in
     * @param string $inPattern Search pattern
     * @return array<string>
     */
    public static function getFiles($inPath = '', $inPattern = '*.epub')
    {
        $res = [];

        // Check path
        if (!is_dir($inPath)) {
            return $res;
        }

        // Get the list of directories
        if (substr($inPath, -1) != DIRECTORY_SEPARATOR) {
            $inPath .= DIRECTORY_SEPARATOR;
        }

        // Add files from the current directory
        $files = glob($inPath . $inPattern, GLOB_MARK | GLOB_NOSORT);
        foreach ($files as $item) {
            if (substr($item, -1) == DIRECTORY_SEPARATOR) {
                continue;
            }
            $res[] = $item;
        }

        // Scan sub directories
        $paths = glob($inPath . '*', GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);
        foreach ($paths as $path) {
            $res = array_merge($res, static::getFiles($path, $inPattern));
        }

        sort($res);

        return $res;
    }
}
