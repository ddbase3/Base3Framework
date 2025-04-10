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
use Base3\Core\ServiceLocator;

/* autoloader */
require DIR_SRC . 'Core/Autoloader.php';
\Base3\Core\Autoloader::register();

/* service locator */
$servicelocator = new ServiceLocator();
ServiceLocator::useInstance($servicelocator);
$servicelocator
	->set('servicelocator', $servicelocator, ServiceLocator::SHARED)
	->set(\Base3\Api\IContainer::class, 'servicelocator', ServiceLocator::ALIAS)
	->set('configuration', new \Base3\Configuration\ConfigFile\ConfigFile, ServiceLocator::SHARED)
	->set(\Base3\Api\IConfiguration::class, 'configuration', ServiceLocator::ALIAS)
	->set('classmap', new \Base3\Core\PluginClassMap($servicelocator), ServiceLocator::SHARED)
	->set('serviceselector', \Base3\ServiceSelector\Standard\StandardServiceSelector::getInstance(), ServiceLocator::SHARED)
	;

/* plugins */
$plugins = $servicelocator->get('classmap')->getInstancesByInterface(\Base3\Api\IPlugin::class);
foreach ($plugins as $plugin) $plugin->init();

/* go */
$serviceselector = $servicelocator->get('serviceselector');
$serviceselector->go();
