<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface IClassMap
 *
 * Provides methods to instantiate classes and retrieve plugin metadata.
 *
 * Note:
 * This interface includes "convenience" methods that are implemented by all
 * Base3 class map implementations via AbstractClassMap.
 */
interface IClassMap {

	/**
	 * Instantiates the given class name.
	 *
	 * @param string $class Fully qualified class name
	 * @return object|null Instance of the class, or null if not instantiable
	 */
	public function instantiate(string $class);

	/**
	 * (Re)generates the class map cache.
	 *
	 * @param bool $regenerate If true, force regeneration even if cache exists
	 * @return void
	 */
	public function generate($regenerate = false): void;

	/**
	 * Returns all apps known to this class map.
	 *
	 * @return array
	 */
	public function getApps();

	/**
	 * Retrieves one or more instances matching the given search criteria.
	 *
	 * The $criteria array may contain:
	 * - 'app' (string)       Application identifier
	 * - 'interface' (string) Fully qualified interface name
	 * - 'name' (string)      Logical name as returned by getName()
	 *
	 * @param array $criteria Associative filter array
	 * @return array List of matching instances (may be empty)
	 */
	public function &getInstances(array $criteria = []);

	/**
	 * Convenience: returns all instances that implement $interface across all apps.
	 *
	 * @param string $interface Fully qualified interface name
	 * @return array
	 */
	public function &getInstancesByInterface($interface);

	/**
	 * Convenience: returns all instances that implement $interface within one app.
	 *
	 * @param string $app
	 * @param string $interface Fully qualified interface name
	 * @param bool $retry Internal: if true, do not regenerate again
	 * @return array
	 */
	public function &getInstancesByAppInterface($app, $interface, $retry = false);

	/**
	 * Convenience: returns one instance by app + logical name.
	 *
	 * @param string $app
	 * @param string $name Logical name as returned by getName()
	 * @param bool $retry Internal: if true, do not regenerate again
	 * @return object|null
	 */
	public function &getInstanceByAppName($app, $name, $retry = false);

	/**
	 * Convenience: returns one instance by interface + logical name.
	 *
	 * @param string $interface Fully qualified interface name
	 * @param string $name Logical name as returned by getName()
	 * @param bool $retry Internal: if true, do not regenerate again
	 * @return object|null
	 */
	public function &getInstanceByInterfaceName($interface, $name, $retry = false);

	/**
	 * Convenience: returns one instance by app + interface + logical name.
	 * If $app is empty, it behaves like getInstanceByInterfaceName().
	 *
	 * @param string $app
	 * @param string $interface Fully qualified interface name
	 * @param string $name Logical name as returned by getName()
	 * @param bool $retry Internal: if true, do not regenerate again
	 * @return object|null
	 */
	public function &getInstanceByAppInterfaceName($app, $interface, $name, $retry = false);

	/**
	 * Returns an array of all registered plugins.
	 *
	 * @return array List of plugin definitions
	 */
	public function getPlugins();
}
