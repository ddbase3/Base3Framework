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
 * Interface IServiceRegistry
 *
 * Generic registry for multiple named instances of a given service interface.
 *
 * The registry is configured for one specific interface (e.g. IFileStorage::class)
 * and provides access to multiple named instances (e.g. "default", "archive").
 *
 * Return type is intentionally `object` to keep this interface generic.
 * Implementations should validate that returned instances implement the configured interface.
 */
interface IServiceRegistry {

	/**
	 * Returns the named service instance.
	 *
	 * @throws \RuntimeException If the name is unknown or the instance does not match the configured interface.
	 */
	public function get(string $name): object;

	/**
	 * True if a named instance is defined.
	 */
	public function has(string $name): bool;

	/**
	 * Returns the default instance configured for this registry.
	 *
	 * @throws \RuntimeException If the default instance cannot be resolved.
	 */
	public function getDefault(): object;

	/**
	 * Lists all defined instance names.
	 *
	 * @return string[]
	 */
	public function listNames(): array;
}
