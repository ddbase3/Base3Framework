<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

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

