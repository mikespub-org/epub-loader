<?php
/**
 * ActionHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader;

use Exception;

/** @phpstan-consistent-constructor */
class ActionHandler
{
    /** @var array<string, string> */
    public static array $actions = [];
    /** @var array<mixed> */
    protected $dbConfig;
    /** @var CalibreDbLoader */
    protected $db;
    /** @var string */
    public $cacheDir;
    /** @var string */
    public $dbFileName;
    /** @var array<mixed> */
    protected $gErrorArray;
    /** @var RequestHandler */
    protected $request;

    /**
     * Summary of __construct
     * @param array<mixed> $dbConfig
     * @param string|null $cacheDir
     */
    public function __construct($dbConfig, $cacheDir = null)
    {
        $this->gErrorArray = [];
        $this->dbConfig = $dbConfig;
        $this->cacheDir = $cacheDir ?? dirname(__DIR__) . '/cache';
        // Init database file
        $dbPath = $this->dbConfig['db_path'];
        $this->dbFileName = $dbPath . DIRECTORY_SEPARATOR . 'metadata.db';
        // Open the database
        if (is_file($this->dbFileName)) {
            $this->db = new CalibreDbLoader($this->dbFileName);
        }
    }

    /**
     * Summary of handle
     * @param string $action
     * @param RequestHandler $request
     * @return mixed
     */
    public function handle($action, $request)
    {
        $this->request = $request;
        switch ($action) {
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of addError
     * @param string $file
     * @param mixed $message
     * @return void
     */
    public function addError($file, $message)
    {
        $this->gErrorArray[$file] = $message;
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
     * Summary of getHandler
     * @param string $action
     * @param array<mixed> $dbConfig
     * @param string|null $cacheDir
     * @return ActionHandler
     */
    public static function getHandler($action, $dbConfig, $cacheDir = null)
    {
        $handlerClass = static::hasAction($action);
        if (!$handlerClass) {
            throw new Exception('Invalid handler for action ' . $action . ' in class ' . static::class);
        }
        // return specific action handler
        return new $handlerClass($dbConfig, $cacheDir);
    }

    /**
     * Summary of hasAction
     * @param string $action
     * @return string|bool
     */
    public static function hasAction($action)
    {
        static::initActions();
        if (array_key_exists($action, static::$actions)) {
            return static::$actions[$action];
        }
        return false;
    }

    /**
     * Summary of initActions
     * @return void
     */
    public static function initActions()
    {
        if (count(static::$actions) > 0) {
            return;
        }
        $handlers = [
            \Marsender\EPubLoader\Handlers\CacheHandler::class,
            \Marsender\EPubLoader\Handlers\DatabaseHandler::class,
            \Marsender\EPubLoader\Handlers\ExportHandler::class,
            \Marsender\EPubLoader\Handlers\ImportHandler::class,
            \Marsender\EPubLoader\Handlers\MetadataHandler::class,
            \Marsender\EPubLoader\Handlers\TestHandler::class,
            \Marsender\EPubLoader\Metadata\GoodReads\GoodReadsHandler::class,
            \Marsender\EPubLoader\Metadata\GoogleBooks\GoogleBooksHandler::class,
            \Marsender\EPubLoader\Metadata\OpenLibrary\OpenLibraryHandler::class,
            \Marsender\EPubLoader\Metadata\WikiData\WikiDataHandler::class,
        ];
        // add ExtraActions::class or whatever this is if needed
        if (!in_array(static::class, $handlers)) {
            $handlers[] = static::class;
        }
        foreach ($handlers as $handlerClass) {
            foreach ($handlerClass::getActions() as $action) {
                static::$actions[$action] = $handlerClass;
            }
        }
    }

    /**
     * Summary of getActions
     * @return array<string>
     */
    public static function getActions()
    {
        if ($parent = get_parent_class(static::class)) {
            $actions = get_class_methods(static::class);
            $inherited = get_class_methods($parent);
            $actions = array_diff($actions, $inherited);
        } else {
            $actions = [];
        }
        return $actions;
    }
}
