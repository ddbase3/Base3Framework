<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IBootstrap;
use Base3\Api\IClassMap;
use Base3\Api\IContainer;
use Base3\Api\IPlugin;
use Base3\Api\IRequest;
use Base3\Core\Request;
use Base3\Core\ServiceLocator;
use Base3\Configuration\ConfigFile\ConfigFile;
use Base3\Configuration\Api\IConfiguration;
use Base3\Core\PluginClassMap;
use Base3\Hook\IHookManager;
use Base3\Hook\IHookListener;
use Base3\Hook\HookManager;
use Base3\ServiceSelector\Standard\StandardServiceSelector;
use Base3\ServiceSelector\Api\IServiceSelector;

class Bootstrap implements IBootstrap {

	public function run(): void {

		// service locator
		$servicelocator = new ServiceLocator();
		ServiceLocator::useInstance($servicelocator);
		$servicelocator
			->set('servicelocator', $servicelocator, ServiceLocator::SHARED)
			->set(IRequest::class, Request::fromGlobals(), ServiceLocator::SHARED)
			->set(IContainer::class, 'servicelocator', ServiceLocator::ALIAS)
			->set(IHookManager::class, fn() => new HookManager, ServiceLocator::SHARED)
			->set('configuration', new ConfigFile, ServiceLocator::SHARED)
			->set(IConfiguration::class, 'configuration', ServiceLocator::ALIAS)
			->set('classmap', new PluginClassMap($servicelocator), ServiceLocator::SHARED)
			->set(IClassMap::class, 'classmap', ServiceLocator::ALIAS)
			->set(IServiceSelector::class, StandardServiceSelector::getInstance(), ServiceLocator::SHARED)
			;

		// hooks
		$hookManager = $servicelocator->get(IHookManager::class);
		$listeners = $servicelocator->get(IClassMap::class)->getInstancesByInterface(IHookListener::class);
		foreach ($listeners as $listener) $hookManager->addHookListener($listener);
		$hookManager->dispatch('bootstrap.init');

		// plugins
		$plugins = $servicelocator->get(IClassMap::class)->getInstancesByInterface(IPlugin::class);
		foreach ($plugins as $plugin) $plugin->init();
		$hookManager->dispatch('bootstrap.start');

		// go
		$serviceselector = $servicelocator->get(IServiceSelector::class);
		echo $serviceselector->go();
		$hookManager->dispatch('bootstrap.finish');
	}
}
