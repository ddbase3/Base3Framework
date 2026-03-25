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
 * Interface IContainer
 *
 * Defines a service container interface for managing and retrieving shared objects and parameters.
 */
interface IContainer {

	const SHARED = 1;        // Service is shared (singleton-style)
	const NOOVERWRITE = 2;   // Prevent overwriting an existing entry
	const ALIAS = 4;         // Entry is an alias to another service
	const PARAMETER = 8;     // Entry is a plain value (parameter), not a service

	/**
	 * Returns a list of all registered service names.
	 *
	 * @return array<string> List of service or parameter keys
	 */
	public function getServiceList(): array;

	/**
	 * Registers a service or parameter in the container.
	 *
	 * @param string $name Service identifier
	 * @param mixed $classDefinition Closure, object, class name, or value
	 * @param int $flags Optional combination of self::SHARED, NOOVERWRITE, ALIAS, PARAMETER
	 * @return IContainer Fluent interface for chaining
	 */
	public function set(string $name, $classDefinition, $flags = 0): IContainer;

	/**
	 * Removes a service or parameter from the container.
	 *
	 * @param string $name Name of the service to remove
	 * @return void
	 */
	public function remove(string $name);

	/**
	 * Checks whether a service or parameter with the given name exists.
	 *
	 * @param string $name Name of the service
	 * @return bool True if the service is registered, false otherwise
	 */
	public function has(string $name): bool;

	/**
	 * Retrieves a service or parameter by name.
	 *
	 * @param string $name Name of the service
	 * @return mixed The resolved service instance or parameter value
	 */
	public function get(string $name);

}

