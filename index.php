<?php

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

/* Debug mode - 0: aus, 1: an, ggfs noch höhere Stufen? */
putenv('DEBUG=1');

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

/* error handling */
ini_set('log_errors', '1');
ini_set('error_log', DIR_TMP . 'php-error.log');
ini_set('memory_limit', '256M');
ini_set('display_errors', getenv('DEBUG') ? 1 : 0);
ini_set('display_startup_errors', getenv('DEBUG') ? 1 : 0);
error_reporting(getenv('DEBUG') ? E_ALL | E_STRICT : 0);

/* autoloader: Composer (optional) */
$pluginComposerAutoload = DIR_ROOT . 'vendor/autoload.php';
if (file_exists($pluginComposerAutoload)) require_once $pluginComposerAutoload;

/* autoloader */
require DIR_SRC . 'Core/Autoloader.php';
\Base3\Core\Autoloader::register();

/* go */
(new \Base3\Core\Bootstrap())->run();
