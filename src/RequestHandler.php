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
    /** @var array<mixed> */
    public $gConfig = [];
    /** @var string */
    public $handlerClass;
    /** @var string|null */
    public $cacheDir;
    /** @var string */
    public $template;
    /** @var array<mixed> */
    protected $gErrorArray;

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
        $this->template = 'index.html';
    }

    /**
     * Summary of loadConfig
     * @param array<mixed> $config
     * @return void
     */
    public function loadConfig($config)
    {
        $this->gConfig = array_merge($this->gConfig, $config);
    }

    /**
     * Summary of request
     * @param string|null $action
     * @param string|int|null $dbNum
     * @return array<mixed>|string|null
     */
    public function request($action = null, $dbNum = null)
    {
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
            $result['errors'] = $this->getErrors();
            if (is_file(dirname(__DIR__) . '/templates/' . $action . '.html')) {
                $this->template = $action . '.html';
            }
            return $result;
        }
        if (!isset($action)) {
            // Display the available actions
            $result = ['actions' => $this->gConfig['actions']];
            $result['errors'] = $this->getErrors();
            $this->template = 'actions.html';
            return $result;
        }
        // Display databases
        $result = [];
        $result['action'] = $action;
        $result['actionTitle'] = $this->gConfig['actions'][$action];
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
     * Summary of doAction
     * @param string $action
     * @param string|int $dbNum
     * @return array<mixed>|string|null
     */
    protected function doAction($action, $dbNum)
    {
        $result = null;
        if (!array_key_exists($action, $this->gConfig['actions'])) {
            die('Invalid action');
        }
        if (!isset($this->gConfig['databases'][$dbNum])) {
            die('Incorrect database num: ' . $dbNum);
        }
        $dbConfig = $this->gConfig['databases'][$dbNum];
        $dbPath = $dbConfig['db_path'];
        if (!is_dir($dbPath)) {
            if (!mkdir($dbPath, 0755, true)) {
                die('Cannot create directory: ' . $dbPath);
            }
        }
        // @todo remove this in the future
        if (!$this->handlerClass::hasAction($action)) {
            $fileName = sprintf('%s%s%s%saction_%s.php', dirname(__DIR__), DIRECTORY_SEPARATOR, 'app', DIRECTORY_SEPARATOR, $action);
            if (!file_exists($fileName)) {
                die('Incorrect action file: ' . $fileName);
            }
            $result = require($fileName);
            return $result;
        }
        $dbConfig['create_db'] = $this->gConfig['create_db'];
        $handler = new $this->handlerClass($dbConfig, $this->cacheDir);
        try {
            $result = $handler->handle($action);
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
