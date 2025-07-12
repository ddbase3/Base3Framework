<?php declare(strict_types=1);

namespace Base3\Configuration\Api;

/**
 * Interface IConfiguration
 *
 * Defines methods for accessing and modifying configuration data.
 */
interface IConfiguration {

	/**
	 * Retrieves configuration data.
	 *
	 * If $configuration is an empty string, the full configuration is returned.
	 *
	 * @param string $configuration Optional configuration section or key
	 * @return mixed The requested configuration data
	 */
	public function get($configuration = "");

	/**
	 * Sets configuration data.
	 *
	 * If $configuration is empty, the root configuration is replaced.
	 *
	 * @param mixed $data The data to set (e.g. array, scalar, etc.)
	 * @param string $configuration Optional section/key to target
	 * @return void
	 */
	public function set($data, $configuration = "");

	/**
	 * Saves the current configuration state (e.g. to file or database).
	 *
	 * @return void
	 */
	public function save();

}

