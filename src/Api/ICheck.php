<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface ICheck
 *
 * Provides a method to check whether all required dependencies for a service are available.
 */
interface ICheck {

	/**
	 * Checks if all necessary dependencies are available and the service is usable.
	 *
	 * This method is typically used in service containers or plugin systems to ensure
	 * that a component can be safely used (e.g. required extensions, files, or config present).
	 *
	 * @return void
	 */
	public function checkDependencies();

}

