<?php declare(strict_types=1);

namespace Base3\Route\Api;

/**
 * Contract for a route.
 * Implementations receive dependencies via constructor (DI).
 */
interface IRoute {

	/**
	 * Check if the given path matches this route.
	 *
	 * @param string $path Absolute path from REQUEST_URI without query string (leading slash included).
	 * @return array|null Associative match data on success, or null if not matched.
	 */
	public function match(string $path): ?array;

	/**
	 * Produce the final response body (implementations may set HTTP headers).
	 *
	 * @param array $match Match data previously returned by match().
	 * @return string Response body.
	 */
	public function dispatch(array $match): string;
}

