<?php

/* Debug mode - 0: aus, 1: an, ggfs noch höhere Stufen? */
define('DEBUG', 1);

/* error handling */
ini_set('display_errors', DEBUG ? 1 : 0);
ini_set('display_startup_errors', DEBUG ? 1 : 0);
error_reporting(DEBUG ? E_ALL | E_STRICT : 0);

/* define directories constants */
define('DIR_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
define('DIR_CNF', DIR_ROOT . 'cnf' . DIRECTORY_SEPARATOR);
define('DIR_LANG', DIR_ROOT . 'lang' . DIRECTORY_SEPARATOR);
define('DIR_SRC', DIR_ROOT . 'src' . DIRECTORY_SEPARATOR);
define('DIR_LOCAL', DIR_ROOT . 'local' . DIRECTORY_SEPARATOR);
define('DIR_PLUGIN', DIR_ROOT . 'plugin' . DIRECTORY_SEPARATOR);
define('DIR_TMP', DIR_ROOT . 'tmp' . DIRECTORY_SEPARATOR);
define('DIR_TPL', DIR_ROOT . 'tpl' . DIRECTORY_SEPARATOR);
define('DIR_USERFILES', DIR_ROOT . 'userfiles' . DIRECTORY_SEPARATOR);

/* uses */
use Base3\ServiceLocator;

/* autoloader */
require DIR_SRC . 'Autoloader.php';
Autoloader::register();
require DIR_SRC . 'PluginAutoloader.php';
PluginAutoloader::register();

/* service locator */
$servicelocator = ServiceLocator::getInstance()
	->set('configuration', new \Configuration\ConfigFile\ConfigFile, ServiceLocator::SHARED)
	->set('classmap', new \Base3\PluginClassMap, ServiceLocator::SHARED)
	->set('serviceselector', \ServiceSelector\Standard\StandardServiceSelector::getInstance(), ServiceLocator::SHARED)
	;
$plugins = $servicelocator->get('classmap')->getInstancesByInterface('Api\\IPlugin');
foreach ($plugins as $plugin) $plugin->init();

/* go */
$serviceselector = $servicelocator->get('serviceselector');
$serviceselector->go();
