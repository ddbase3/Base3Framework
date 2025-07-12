<?php declare(strict_types=1);

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

