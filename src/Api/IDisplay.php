<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface IDisplay
 *
 * Extends IOutput to support setting data for display purposes.
 */
interface IDisplay extends IOutput {

	/**
	 * Sets the data to be displayed by the output component.
	 *
	 * @param mixed $data The data structure to be rendered (e.g. array, object, scalar)
	 * @return void
	 */
	public function setData($data);

}

