<?php declare(strict_types=1);

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

