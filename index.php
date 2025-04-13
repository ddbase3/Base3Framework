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
define('DIR_LANG', DIR_ROOT . 'lang' . DIRECTORY_SEPARATOR);
define('DIR_SRC', DIR_ROOT . 'src' . DIRECTORY_SEPARATOR);
define('DIR_LOCAL', DIR_ROOT . 'local' . DIRECTORY_SEPARATOR);
define('DIR_PLUGIN', DIR_ROOT . 'plugin' . DIRECTORY_SEPARATOR);
define('DIR_TMP', DIR_ROOT . 'tmp' . DIRECTORY_SEPARATOR);
define('DIR_TPL', DIR_ROOT . 'tpl' . DIRECTORY_SEPARATOR);
define('DIR_USERFILES', DIR_ROOT . 'userfiles' . DIRECTORY_SEPARATOR);

/* uses */
use Base3\Core\Autoloader;
use Base3\Core\ServiceLocator;
use Base3\Api\IContainer;
use Base3\Configuration\ConfigFile\ConfigFile;
use Base3\Configuration\Api\IConfiguration;
use Base3\Core\PluginClassMap;
use Base3\Api\IClassMap;
use Base3\ServiceSelector\Standard\StandardServiceSelector;
use Base3\ServiceSelector\Api\IServiceSelector;
use Base3\Api\IPlugin;

/* autoloader */
require DIR_SRC . 'Core/Autoloader.php';
Autoloader::register();

/* autoloader: Composer (optional) */
$pluginComposerAutoload = DIR_PLUGIN . 'vendor/autoload.php';
if (file_exists($pluginComposerAutoload)) require_once $pluginComposerAutoload;

/* service locator */
$servicelocator = new ServiceLocator();
ServiceLocator::useInstance($servicelocator);
$servicelocator
	->set('servicelocator', $servicelocator, ServiceLocator::SHARED)
	->set(IContainer::class, 'servicelocator', ServiceLocator::ALIAS)
	->set('configuration', new ConfigFile, ServiceLocator::SHARED)
	->set(IConfiguration::class, 'configuration', ServiceLocator::ALIAS)
	->set('classmap', new PluginClassMap($servicelocator), ServiceLocator::SHARED)
	->set(IClassMap::class, 'classmap', ServiceLocator::ALIAS)
	->set(IServiceSelector::class, StandardServiceSelector::getInstance(), ServiceLocator::SHARED)
	;

/* plugins */
$plugins = $servicelocator->get(IClassMap::class)->getInstancesByInterface(IPlugin::class);
foreach ($plugins as $plugin) $plugin->init();

/* go */
$serviceselector = $servicelocator->get(IServiceSelector::class);
$serviceselector->go();

