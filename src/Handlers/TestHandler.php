<?php

/**
 * TestHandler class
 */

namespace Marsender\EPubLoader\Handlers;

use Marsender\EPubLoader\ActionHandler;
use SebLucas\EPubMeta\App\Handler as MetaAppHandler;
use Throwable;

class TestHandler extends ActionHandler
{
    /**
     * Summary of test
     * @return string
     */
    public function test()
    {
        return 'ok';
    }

    /**
     * Summary of meta
     * @return null
     */
    public function meta()
    {
        // modify this to point to your book directory
        $dbPath = $this->dbConfig['db_path'];
        $epubPath = $this->dbConfig['epub_path'];
        $bookdir = $dbPath . DIRECTORY_SEPARATOR . $epubPath . DIRECTORY_SEPARATOR;

        // allow ebooks in subfolders
        $recursive = true;
        // baseurl for assets etc. (relative to this entrypoint)
        $endpoint = $this->request->getEndpoint();
        $basedir = dirname(str_replace('/index.php', '', $endpoint));
        $baseurl = $basedir . '/vendor/mikespub/php-epub-meta';
        // rename file as new "$author-$title.epub" after update
        $rename = false;
        // template files directory
        //$templatedir = dirname(__DIR__, 2) . '/vendor/mikespub/php-epub-meta/templates/';
        $templatedir = dirname(__DIR__, 2) . '/templates/php-epub-meta/';
        // cache directory for Google Books API calls (optional)
        $cachedir = null;
        if (!empty($this->cacheDir)) {
            $cachedir = $this->cacheDir . '/google/titles/';
        }
        $parent = ['title' => 'EPub Loader', 'link' => './'];

        // @todo let RequestHandler know we handled this
        putenv('PHPUNIT_TESTING=1');

        // 1. create config array with the options above
        $config = [
            'bookdir' => $bookdir,
            'recursive' => $recursive,
            'baseurl' => $baseurl,
            'rename' => $rename,
            'templatedir' => $templatedir,
            'cachedir' => $cachedir,
            'parent' => $parent,
        ];
        // 2. instantiate the app handler with the config array
        $handler = new MetaAppHandler($config);
        try {
            // 3. get request params from PHP globals or elsewhere
            $params = $handler->getRequestFromGlobals();
            // 4. handle the request and get back the result
            $result = $handler->handle($params);
            if (!is_null($result)) {
                // 5.a. output the result
                echo $result;
            } else {
                // 5.b. some actions have no output, e.g. redirect after save
            }
        } catch (Throwable $e) {
            // 6. catch any errors and handle them
            error_log($e);
            echo $e->getMessage();
        }
        return null;
    }

    /**
     * Summary of testCallback
     * @param mixed $id
     * @param mixed $info
     * @return bool
     */
    public static function testCallback($id, $info)
    {
        $pieces = explode('\\', $info::class);
        $className = last($pieces);
        //echo "Callback for {$className} {$id}\n";
        return true;
    }
}
