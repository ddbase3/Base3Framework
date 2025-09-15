<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IBootstrap;
use Base3\Api\IClassMap;
use Base3\Api\IContainer;
use Base3\Api\IPlugin;
use Base3\Api\IRequest;
use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Accesscontrol\No\NoAccesscontrol;
use Base3\Core\Request;
use Base3\Core\ServiceLocator;
use Base3\Configuration\ConfigFile\ConfigFile;
use Base3\Configuration\Api\IConfiguration;
use Base3\Core\PluginClassMap;
use Base3\Hook\IHookManager;
use Base3\Hook\IHookListener;
use Base3\Hook\HookManager;
use Base3\ServiceSelector\Api\IServiceSelector;
use Base3\ServiceSelector\Standard\StandardServiceSelector;

class Bootstrap implements IBootstrap {

	public function run(): void {

		// container
		$container = new ServiceLocator();
		ServiceLocator::useInstance($container);
		$container
			->set('servicelocator', $container, IContainer::SHARED)
			->set(IRequest::class, fn() => Request::fromGlobals(), IContainer::SHARED)
			->set(IContainer::class, 'servicelocator', IContainer::ALIAS)
			->set(IHookManager::class, fn() => new HookManager(), IContainer::SHARED)
			->set('configuration', fn() => new ConfigFile(), IContainer::SHARED)
			->set(IConfiguration::class, 'configuration', IContainer::ALIAS)
			->set('classmap', fn($c) => new PluginClassMap($c->get(IContainer::class)), IContainer::SHARED)
			->set(IClassMap::class, 'classmap', IContainer::ALIAS)
                        ->set('accesscontrol', fn($c) => new NoAccesscontrol(), IContainer::SHARED)
                        ->set(IAccesscontrol::class, 'accesscontrol', IContainer::ALIAS)
			->set(IServiceSelector::class, fn($c) => new StandardServiceSelector($c), IContainer::SHARED)
			->set('middlewares', [])
		;

		// hooks
		$hookManager = $container->get(IHookManager::class);
		$listeners = $container->get(IClassMap::class)->getInstancesByInterface(IHookListener::class);
		foreach ($listeners as $listener) $hookManager->addHookListener($listener);
		$hookManager->dispatch('bootstrap.init');

		// plugins
		$plugins = $container->get(IClassMap::class)->getInstancesByInterface(IPlugin::class);
		foreach ($plugins as $plugin) $plugin->init();
		$hookManager->dispatch('bootstrap.start');

		// go
		$serviceSelector = $container->get(IServiceSelector::class);
		echo $serviceSelector->go();
		$hookManager->dispatch('bootstrap.finish');
	}
}

