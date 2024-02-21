<?php
/**
 * Epub loader application
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 * @author     mikespub
 */

namespace Marsender\EPubLoader\App;

use Marsender\EPubLoader\RequestHandler;

//------------------------------------------------------------------------------
// Include files
//------------------------------------------------------------------------------

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Include config file
$fileName = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
if (!file_exists($fileName)) {
    die('Missing configuration file: ' . $fileName);
}
// Global vars
$gConfig = [];
require_once($fileName);
/** @var array<mixed> $gConfig */

//------------------------------------------------------------------------------
// Start application
//------------------------------------------------------------------------------

// Get the url parameters
$action = $_GET['action'] ?? null;
$_GET['dbnum'] ??= '';
$dbNum = ($_GET['dbnum'] !== '') ? (int) $_GET['dbnum'] : null;
$urlParams = $_GET;

$data = [
    'endpoint' => $gConfig['endpoint'] ?? './index.php',
    'app_name' => $gConfig['app_name'] ?? 'Epub Loader',
    'version' => $gConfig['version'] ?? '2.3',
    'admin_email' => empty($gConfig['admin_email']) ? '' : str_rot13($gConfig['admin_email']),
];

// you can define extra actions for your app - see example.php
$handler = new RequestHandler($gConfig, ExtraActions::class);
$result = $handler->request($action, $dbNum, $urlParams);

if (is_array($result)) {
    $data = array_merge($data, $result);
} else {
    $data['result'] = $result;
}

$templateDir = null;  // $handler->templateDir = dirname(__DIR__) . '/templates';
$template = null;  // $handler->template;

header('Content-type: text/html; charset=utf-8');

echo $handler->output($data, $templateDir, $template);
