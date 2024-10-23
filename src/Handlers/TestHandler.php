<?php
/**
 * TestHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
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

    public function meta()
    {
        $dbPath = $this->dbConfig['db_path'];
        $epubPath = $this->dbConfig['epub_path'];
        // modify this to point to your book directory
        $bookdir = $dbPath . DIRECTORY_SEPARATOR . $epubPath . DIRECTORY_SEPARATOR;
        // baseurl for assets etc. (relative to this entrypoint)
        $endpoint = $this->request->getEndpoint();
        $basedir = dirname(str_replace('/index.php', '', $endpoint));
        $baseurl = $basedir . '/vendor/mikespub/php-epub-meta';
        // rename file as new "$author-$title.epub" after update
        $rename = false;

        // @todo let RequestHandler know we handled this
        putenv('PHPUNIT_TESTING=1');

        $handler = new MetaAppHandler($bookdir, $baseurl, $rename);
        try {
            $result = $handler->handle();
            if (!is_null($result)) {
                echo $result;
            }
        } catch (Throwable $e) {
            error_log($e);
            echo $e->getMessage();
        }
        return null;
    }
}
