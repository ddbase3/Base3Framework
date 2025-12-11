<?php

declare(strict_types=1);

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
