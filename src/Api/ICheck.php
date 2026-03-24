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

