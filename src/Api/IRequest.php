<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface IRequest
 *
 * Provides unified access to request data (GET, POST, COOKIE, SESSION, SERVER, FILES)
 * and contextual information about the current execution environment.
 */
interface IRequest {

	const CONTEXT_CLI = 'cli';
	const CONTEXT_WEB_GET = 'web_get';
	const CONTEXT_WEB_POST = 'web_post';
	const CONTEXT_WEB_AJAX = 'web_ajax';
	const CONTEXT_WEB_API = 'web_api';
	const CONTEXT_WEB_UPLOAD = 'web_upload';
	const CONTEXT_CRON = 'cron';
	const CONTEXT_TEST = 'test';
	const CONTEXT_BUILTIN_SERVER = 'builtin_server';

	/**
	 * Returns a value from the GET array.
	 *
	 * @param string $key Parameter name
	 * @param mixed $default Default value if key is not set
	 * @return mixed
	 */
	public function get(string $key, $default = null);

	/**
	 * Returns a value from the POST array.
	 *
	 * @param string $key Parameter name
	 * @param mixed $default Default value if key is not set
	 * @return mixed
	 */
	public function post(string $key, $default = null);

	/**
	 * Returns a value from GET or POST.
	 *
	 * POST takes precedence over GET.
	 * Nested array notation (e.g. "options[type]") is supported.
	 *
	 * @param string $key Parameter name
	 * @param mixed $default Default value if not set in both POST and GET
	 * @return mixed
	 */
	public function request(string $key, $default = null);

	/**
	 * Returns all request parameters merged from POST and GET.
	 *
	 * POST overrides GET in case of duplicate keys.
	 *
	 * @return array<string, mixed>
	 */
	public function allRequest(): array;

	/**
	 * Returns a value from the COOKIE array.
	 *
	 * @param string $key Cookie name
	 * @param mixed $default Default value if key is not set
	 * @return mixed
	 */
	public function cookie(string $key, $default = null);

	/**
	 * Returns a value from the SESSION array.
	 *
	 * @param string $key Session key
	 * @param mixed $default Default value if key is not set
	 * @return mixed
	 */
	public function session(string $key, $default = null);

	/**
	 * Returns a value from the SERVER array.
	 *
	 * @param string $key Server variable name
	 * @param mixed $default Default value if key is not set
	 * @return mixed
	 */
	public function server(string $key, $default = null);

	/**
	 * Returns a value from the FILES array.
	 *
	 * @param string $key File field name
	 * @param mixed $default Default value if key is not set
	 * @return mixed
	 */
	public function files(string $key, $default = null);

	/**
	 * Returns all GET parameters.
	 *
	 * @return array<string, mixed>
	 */
	public function allGet(): array;

	/**
	 * Returns all POST parameters.
	 *
	 * @return array<string, mixed>
	 */
	public function allPost(): array;

	/**
	 * Returns all COOKIE values.
	 *
	 * @return array<string, mixed>
	 */
	public function allCookie(): array;

	/**
	 * Returns all SESSION values.
	 *
	 * @return array<string, mixed>
	 */
	public function allSession(): array;

	/**
	 * Returns all SERVER values.
	 *
	 * @return array<string, mixed>
	 */
	public function allServer(): array;

	/**
	 * Returns all FILES values.
	 *
	 * @return array<string, mixed>
	 */
	public function allFiles(): array;

	/**
	 * Returns the decoded JSON body of the request, if available.
	 *
	 * This is useful for API calls with Content-Type: application/json,
	 * where $_POST is typically empty and the body must be read manually.
	 *
	 * @return array<string, mixed> Parsed JSON data as associative array, or empty array if invalid or missing.
	 */
	public function getJsonBody(): array;

	/**
	 * Determines whether the request is a CLI call.
	 *
	 * @return bool True if CLI, false otherwise
	 */
	public function isCli(): bool;

	/**
	 * Returns the detected context of the current request.
	 *
	 * @return string One of the CONTEXT_* constants
	 */
	public function getContext(): string;
}

