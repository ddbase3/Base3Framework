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

namespace Base3\ServiceSelector\Api;

/**
 * Interface IServiceSelector
 *
 * Defines the entry point for a routed request. The `go()` method is responsible
 * for handling the request and returning the complete response content.
 */
interface IServiceSelector {

	/**
	 * Executes the selected service logic and returns the full response content.
	 *
	 * This is the final rendered output (e.g. full HTML page, JSON response, etc.).
	 *
	 * @return string Rendered response content
	 */
	public function go(): string;

}

