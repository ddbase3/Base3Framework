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

namespace Base3\Middleware\Api;

/**
 * Interface IMiddleware
 *
 * Defines a middleware component that can process a request and delegate to the next handler.
 */
interface IMiddleware {

	/**
	 * Sets the next middleware or handler in the chain.
	 *
	 * @param mixed $next The next middleware or final handler (typically another IMiddleware or callable)
	 * @return void
	 */
	public function setNext($next);

	/**
	 * Processes the current request and optionally calls the next middleware.
	 *
	 * @return string The response content
	 */
	public function process(): string;

}

