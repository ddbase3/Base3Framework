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

