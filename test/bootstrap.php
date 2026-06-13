<?php declare(strict_types=1);

/**
 * PHPUnit Bootstrap
 * - defines directory constants
 * - enables DEBUG for tests
 * - loads Composer autoloaders (root + optional plugin vendors)
 * - registers project autoloader
 */

// --- Environment / Debug ------------------------------------------------------
putenv('DEBUG=1');

// --- Directory constants ------------------------------------------------------
define('DIR_ROOT', rtrim((string)realpath(__DIR__ . '/../'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
define('DIR_SRC', DIR_ROOT . 'src' . DIRECTORY_SEPARATOR);
define('DIR_PLUGIN', DIR_ROOT . 'plugin' . DIRECTORY_SEPARATOR);
define('DIR_TEST', DIR_ROOT . 'test' . DIRECTORY_SEPARATOR);

// --- Log errors ---------------------------------------------------------------
$logDir = DIR_ROOT . 'tmp' . DIRECTORY_SEPARATOR;
$logFile = $logDir . 'phpunit-error.log';

if (!is_dir($logDir)) {
	@mkdir($logDir, 0777, true);
}

// Wenn tmp nicht schreibbar ist, fallback auf /tmp (damit es garantiert klappt)
if (!is_dir($logDir) || !is_writable($logDir)) {
	$logFile = '/tmp/base3-phpunit-error.log';
}

// Datei sicher anlegen (falls möglich), damit PHP nicht auf STDERR zurückfällt
@touch($logFile);

ini_set('log_errors', '1');
ini_set('error_log', $logFile);

// Optional: verhindert, dass Fehler zusätzlich “direkt” ausgegeben werden
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// --- Test-only stubs ----------------------------------------------------------
require_once DIR_TEST . 'Dummy' . DIRECTORY_SEPARATOR . 'PhpUnitPharClassLoaderStub.php';

// --- Autoload: Composer (root) ------------------------------------------------
$rootComposerAutoload = DIR_ROOT . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (is_file($rootComposerAutoload)) {
	require_once $rootComposerAutoload;
}

// --- Autoload: project autoloader --------------------------------------------
require_once DIR_SRC . 'Core' . DIRECTORY_SEPARATOR . 'Autoloader.php';
\Base3\Core\Autoloader::register();

// --- Autoload: Composer (per plugin, optional) --------------------------------
// Only needed if some plugins have their own vendor/ folder with PSR-4 autoloading.
// Safe to keep; it does nothing if no plugin vendors exist.
$pluginAutoloaders = glob(DIR_PLUGIN . '*' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php') ?: [];
foreach ($pluginAutoloaders as $autoloadFile) {
	if (is_file($autoloadFile)) {
		require_once $autoloadFile;
	}
}

// --- Re-apply log target (Framework/Plugins may override ini settings) ---------
ini_set('log_errors', '1');
ini_set('error_log', $logFile);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
