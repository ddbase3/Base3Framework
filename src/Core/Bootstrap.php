<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IBootstrap;
use Base3\Api\IPlugin;
use Base3\Api\IRequest;
use Base3\Api\IClassMap;
use Base3\Api\IContainer;
use Base3\Configuration\Api\IConfiguration;
use Base3\Configuration\ConfigFile\ConfigFile;
use Base3\Core\PluginClassMap;
use Base3\Core\Request;
use Base3\Core\ServiceLocator;
use Base3\ServiceSelector\Api\IServiceSelector;
use Base3\ServiceSelector\Standard\StandardServiceSelector;

class Bootstrap implements IBootstrap {

	public function run(): void {
		$this->setupEnvironment();
		$this->defineConstants();
		// $this->registerAutoloaders();
		$this->initializeServiceLocator();
		$this->initPlugins();
		$this->startApplication();
	}

	protected function setupEnvironment(): void {
		putenv('DEBUG=1'); // oder aus externem config file lesen
		$debug = getenv('DEBUG');
		ini_set('display_errors', $debug ? '1' : '0');
		ini_set('display_startup_errors', $debug ? '1' : '0');
		error_reporting($debug ? E_ALL | E_STRICT : 0);
	}

	protected function defineConstants(): void {
		define('DIR_ROOT', __DIR__ . '/../../');
		define('DIR_CNF', DIR_ROOT . 'cnf/');
		define('DIR_SRC', DIR_ROOT . 'src/');
		define('DIR_LOCAL', DIR_ROOT . 'local/');
		define('DIR_PLUGIN', DIR_ROOT . 'plugin/');
		define('DIR_TEST', DIR_ROOT . 'test/');
		define('DIR_TMP', DIR_ROOT . 'tmp/');
		define('DIR_TPL', DIR_ROOT . 'tpl/');
		define('DIR_USERFILES', DIR_ROOT . 'userfiles/');
	}

	protected function registerAutoloaders(): void {
		require DIR_SRC . 'Core/Autoloader.php';
		Autoloader::register();

		$composerAutoload = DIR_ROOT . 'vendor/autoload.php';
		if (file_exists($composerAutoload)) {
			require_once $composerAutoload;
		}
	}

	protected function initializeServiceLocator(): void {
		$sl = new ServiceLocator();
		ServiceLocator::useInstance($sl);

		$sl->set('servicelocator', $sl, ServiceLocator::SHARED)
		   ->set(IRequest::class, Request::fromGlobals(), ServiceLocator::SHARED)
		   ->set(IContainer::class, 'servicelocator', ServiceLocator::ALIAS)
		   ->set('configuration', new ConfigFile, ServiceLocator::SHARED)
		   ->set(IConfiguration::class, 'configuration', ServiceLocator::ALIAS)
		   ->set('classmap', new PluginClassMap($sl), ServiceLocator::SHARED)
		   ->set(IClassMap::class, 'classmap', ServiceLocator::ALIAS)
		   ->set(IServiceSelector::class, StandardServiceSelector::getInstance(), ServiceLocator::SHARED);
	}

	protected function initPlugins(): void {
		$plugins = ServiceLocator::getInstance()
			->get(IClassMap::class)
			->getInstancesByInterface(IPlugin::class);

		foreach ($plugins as $plugin) {
			$plugin->init();
		}
	}

	protected function startApplication(): void {
		$selector = ServiceLocator::getInstance()->get(IServiceSelector::class);
		$selector->go();
	}
}

