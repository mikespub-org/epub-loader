<?php
/**
 * DatabaseHandler class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Handlers;

use Marsender\EPubLoader\ActionHandler;
use Marsender\EPubLoader\RequestHandler;
use Exception;

class DatabaseHandler extends ActionHandler
{
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
            case 'notes':
                $colName = $this->request->get('colName');
                $itemId = $this->request->getId('itemId');
                $html = !empty($this->request->get('html')) ? true : false;
                $result = $this->notes($colName, $itemId, $html);
                break;
            case 'resource':
                $hash = $this->request->get('hash');
                $result = $this->resource($hash);
                break;
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of notes
     * @param string|null $colName
     * @param int|null $itemId
     * @param bool $html
     * @return array<mixed>
     */
    public function notes($colName = null, $itemId = null, $html = false)
    {
        $notescount = $this->db->getNotesCount();
        $items = [];
        if (!empty($colName)) {
            if (!empty($itemId)) {
                $items = $this->db->getNotes($colName, [$itemId]);
                if ($html) {
                    $dbNum = $this->dbConfig['db_num'];
                    $endpoint = $this->request->getEndpoint();
                    $items[$itemId]['doc'] = str_replace('calres://', $endpoint . '/resource/' . $dbNum . '?hash=', $items[$itemId]['doc']);
                    $items[$itemId]['doc'] = str_replace('?placement=', '&placement=', $items[$itemId]['doc']);
                }
            } else {
                $items = $this->db->getNotes($colName);
            }
        }
        return ['notescount' => $notescount, 'colName' => $colName, 'itemId' => $itemId, 'items' => $items, 'html' => $html];
    }

    /**
     * Summary of resource
     * @param string|null $hash
     * @return null
     */
    public function resource($hash = null)
    {
        if (empty($hash)) {
            $this->addError($this->dbFileName, "Please specify a resource hash");
            return null;
        }
        [$alg, $digest] = explode('/', $hash);
        $hash = "{$alg}-{$digest}";
        $path = $this->db->getResourcePath($hash);
        if (empty($path)) {
            $this->addError($this->dbFileName, "Please specify a valid resource hash");
            return null;
        }
        $meta = json_decode(file_get_contents($path . '.metadata'), true);
        $ext = strtolower(pathinfo((string) $meta['name'], PATHINFO_EXTENSION));
        $mime = 'application/octet-stream';
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $mime = 'image/jpeg';
                break;
            case 'png':
                $mime = 'image/png';
                break;
        }
        $expires = 60 * 60 * 24 * 14;
        header('Pragma: public');
        header('Cache-Control: max-age=' . $expires);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
        header('Content-Type: ' . $mime);

        readfile($path);
        if (!empty(getenv('PHPUNIT_TESTING'))) {
            return null;
        }
        exit;
    }
}
