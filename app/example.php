<?php
/**
 * Epub loader - example of extra actions defined in your app
 *
 * You can add extra actions on databases and/or epub files by extending
 * the ActionHandler class and adding your own methods here. If you name
 * the class something other than 'ExtraActions', be sure to change this
 * line in index.php:
 *
 * $handler = new RequestHandler($gConfig, ExtraActions::class);
 *
 * and run 'composer dump-autoload -o' to let the autoloader find it
 */

namespace Marsender\EPubLoader\App;

use Marsender\EPubLoader\ActionHandler;

/**
 * Extra actions defined as an example - add your own here or replace
 */
class ExtraActions extends ActionHandler
{
    /**
     * Summary of hello_world
     * @return array<mixed>|string|null
     */
    public function hello_world()
    {
        // you can access the selected database config here (name, db_path, epub_path, ...)
        $dbName = $this->dbConfig['name'];
        // any additional parameters you need to grab yourself
        $name = $_GET['name'] ?? 'World!';

        // do the action and build the result
        $result = 'Hello, ' . $name . ' for database ' . $dbName;

        // return an array containing the result(s) or see below
        return ['result' => $result, 'name' => $name];
    }

    /**
     * Summary of goodbye
     * @return array<mixed>|string|null
     */
    public function goodbye()
    {
        // you can report errors in your action too
        $this->addError(basename(__FILE__), 'Why leave so soon?');

        // or return a simple text string if you want
        return 'Goodbye from ' . $this->dbFileName;
    }
}
