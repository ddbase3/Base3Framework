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

