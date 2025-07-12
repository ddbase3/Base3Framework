<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface IComparable
 *
 * Provides a method for comparing two objects for sorting purposes.
 */
interface IComparable {

	/**
	 * Compares the current object with another object.
	 *
	 * Returns:
	 * - `-1` if this object is smaller,
	 * - `0` if equal,
	 * - `1` if greater.
	 *
	 * This is typically used for custom sorting of object arrays.
	 *
	 * @param mixed $o Object to compare with
	 * @return int Comparison result: -1, 0, or 1
	 */
	public function compareTo($o);

}

