<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface IClassMap
 *
 * Provides methods to instantiate classes and retrieve plugin metadata.
 */
interface IClassMap {

	/**
	 * Instantiates the given class name if it exists in the class map.
	 *
	 * @param string $class Fully qualified class name
	 * @return object|null Instance of the class, or null if not found
	 */
	public function instantiate(string $class);

	/**
	 * Returns an array of all registered plugins.
	 *
	 * The format and contents depend on the specific implementation.
	 *
	 * @return array List of plugin definitions
	 */
	public function getPlugins();

}

