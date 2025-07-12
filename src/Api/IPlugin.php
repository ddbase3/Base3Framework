<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface IPlugin
 *
 * Defines a plugin that can be initialized with a service container and perform setup logic.
 */
interface IPlugin {

	/**
	 * Constructs the plugin with access to the dependency injection container.
	 *
	 * @param IContainer $container The shared service container
	 */
	public function __construct(IContainer $container);

	/**
	 * Initializes the plugin (e.g. registers services, performs setup).
	 *
	 * Called after construction to allow the plugin to configure its dependencies.
	 *
	 * @return void
	 */
	public function init();

}

