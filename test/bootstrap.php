<?php

declare(strict_types=1);

// Wurzelverzeichnisse definieren
define('DIR_ROOT', realpath(__DIR__ . '/../') . '/');
define('DIR_SRC', DIR_ROOT . 'src/');
define('DIR_PLUGIN', DIR_ROOT . 'plugin/');
define('DIR_TEST', DIR_ROOT . 'test/');

// Autoloader laden und registrieren
require DIR_SRC . 'Core/Autoloader.php';
\Base3\Core\Autoloader::register();

// Optional: Debug aktivieren
putenv('DEBUG=1');

