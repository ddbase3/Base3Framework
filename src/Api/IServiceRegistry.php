<?php declare(strict_types=1);

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
