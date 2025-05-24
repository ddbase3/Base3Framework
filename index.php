<?php

/* Debug mode - 0: aus, 1: an, ggfs noch höhere Stufen? */
putenv('DEBUG=1');

/* error handling */
ini_set('display_errors', getenv('DEBUG') ? 1 : 0);
ini_set('display_startup_errors', getenv('DEBUG') ? 1 : 0);
error_reporting(getenv('DEBUG') ? E_ALL | E_STRICT : 0);

/* define directories constants */
define('DIR_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
define('DIR_CNF', DIR_ROOT . 'cnf' . DIRECTORY_SEPARATOR);
define('DIR_SRC', DIR_ROOT . 'src' . DIRECTORY_SEPARATOR);
define('DIR_LOCAL', DIR_ROOT . 'local' . DIRECTORY_SEPARATOR);
define('DIR_PLUGIN', DIR_ROOT . 'plugin' . DIRECTORY_SEPARATOR);
define('DIR_TEST', DIR_ROOT . 'test' . DIRECTORY_SEPARATOR);
define('DIR_TMP', DIR_ROOT . 'tmp' . DIRECTORY_SEPARATOR);
define('DIR_TPL', DIR_ROOT . 'tpl' . DIRECTORY_SEPARATOR);
define('DIR_USERFILES', DIR_ROOT . 'userfiles' . DIRECTORY_SEPARATOR);

/* autoloader */
require DIR_SRC . 'Core/Autoloader.php';
\Base3\Core\Autoloader::register();

/* autoloader: Composer (optional) */
$pluginComposerAutoload = DIR_ROOT . 'vendor/autoload.php';
if (file_exists($pluginComposerAutoload)) require_once $pluginComposerAutoload;

/* go */
(new \Base3\Core\Bootstrap())->run();
