<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IRequest;

class Request implements IRequest {

	protected \ArrayAccess|array $get = [];
	protected \ArrayAccess|array $post = [];
	protected \ArrayAccess|array $cookie = [];
	protected \ArrayAccess|array $session = [];
	protected \ArrayAccess|array $server = [];
	protected \ArrayAccess|array $files = [];

	private ?array $jsonBody = null;

	public function __construct() {}

	/** Initialize from PHP superglobals. */
	public function initFromGlobals(): void {
		$this->get = $_GET;
		$this->post = $_POST;
		$this->cookie = $_COOKIE;
		$this->session = $_SESSION ?? [];
		$this->server = $_SERVER;
		$this->files = $_FILES;

		if ($this->isCli()) {
			$this->parseCliArgs();
		}
	}

	/** Create a new instance initialized from superglobals. */
	public static function fromGlobals(): self {
		$self = new self();
		$self->initFromGlobals();
		return $self;
	}

	/** Parse CLI arguments into GET-like keys. */
	protected function parseCliArgs(): void {
		$args = $_SERVER['argv'] ?? [];
		array_shift($args);

		foreach ($args as $arg) {
			if (preg_match('/^--([^=]+)=(.*)$/', $arg, $matches)) {
				$this->get[$matches[1]] = $matches[2];
			} elseif (preg_match('/^--([^=]+)$/', $arg, $matches)) {
				$this->get[$matches[1]] = true;
			}
		}
	}

	/**
	 * Convert a source to a plain array.
	 * Accepts arrays, ArrayAccess or Traversable.
	 */
	protected function toArray(array|\ArrayAccess $source): array {
		if (is_array($source)) {
			return $source;
		}
		if ($source instanceof \Traversable) {
			return iterator_to_array($source);
		}
		$result = [];
		foreach ($source as $k => $v) {
			$result[$k] = $v;
		}
		return $result;
	}

	/** Resolve "array notation" keys like "options[type]". */
	protected function resolve(string $key, array $source, $default) {
		if (array_key_exists($key, $source)) {
			return $source[$key];
		}
		if (str_contains($key, '[')) {
			$parts = preg_split('/\[|\]/', $key, -1, PREG_SPLIT_NO_EMPTY);
			foreach ($parts as $part) {
				if (!is_array($source) || !array_key_exists($part, $source)) {
					return $default;
				}
				$source = $source[$part];
			}
			return $source;
		}
		return $default;
	}

	/** Deep-merge GET and POST with POST precedence. */
	protected function mergeRecursiveRequest(array $get, array $post): array {
		$result = $get;
		foreach ($post as $key => $value) {
			if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
				$result[$key] = $this->mergeRecursiveRequest($result[$key], $value);
			} else {
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/** Return a GET parameter (array notation supported). */
	public function get(string $key, $default = null) {
		return $this->resolve($key, $this->toArray($this->get), $default);
	}

	/** Return a POST parameter (array notation supported). */
	public function post(string $key, $default = null) {
		return $this->resolve($key, $this->toArray($this->post), $default);
	}

	/** Return a parameter from POST or GET (POST strictly takes precedence). */
	public function request(string $key, $default = null) {
		$postArr = $this->toArray($this->post);
		$getArr = $this->toArray($this->get);

		// strict precedence: if POST contains the key (even if null), use it
		if (array_key_exists($key, $postArr)) {
			return $postArr[$key];
		}
		if (str_contains($key, '[')) {
			$parts = preg_split('/\[|\]/', $key, -1, PREG_SPLIT_NO_EMPTY);

			// Check POST path existence strictly
			$cursor = $postArr;
			$existsInPost = true;
			foreach ($parts as $part) {
				if (!is_array($cursor) || !array_key_exists($part, $cursor)) {
					$existsInPost = false;
					break;
				}
				$cursor = $cursor[$part];
			}
			if ($existsInPost) {
				return $cursor; // may be null
			}

			// Fallback to GET
			return $this->resolve($key, $getArr, $default);
		}

		// Fallback to GET (flat key)
		return $this->resolve($key, $getArr, $default);
	}

	/** Return all request parameters merged (POST > GET, deep). */
	public function allRequest(): array {
		return $this->mergeRecursiveRequest($this->toArray($this->get), $this->toArray($this->post));
	}

	/** Return a COOKIE value (array notation supported). */
	public function cookie(string $key, $default = null) {
		return $this->resolve($key, $this->toArray($this->cookie), $default);
	}

	/** Return a SESSION value (array notation supported). */
	public function session(string $key, $default = null) {
		return $this->resolve($key, $this->toArray($this->session), $default);
	}

	/** Return a SERVER value (array notation supported). */
	public function server(string $key, $default = null) {
		return $this->resolve($key, $this->toArray($this->server), $default);
	}

	/** Return a FILES value (array notation supported). */
	public function files(string $key, $default = null) {
		return $this->resolve($key, $this->toArray($this->files), $default);
	}

	/** Return all GET parameters. */
	public function allGet(): array {
		return $this->toArray($this->get);
	}

	/** Return all POST parameters. */
	public function allPost(): array {
		return $this->toArray($this->post);
	}

	/** Return all COOKIE values. */
	public function allCookie(): array {
		return $this->toArray($this->cookie);
	}

	/** Return all SESSION values. */
	public function allSession(): array {
		return $this->toArray($this->session);
	}

	/** Return all SERVER values. */
	public function allServer(): array {
		return $this->toArray($this->server);
	}

	/** Return all FILES values. */
	public function allFiles(): array {
		return $this->toArray($this->files);
	}

	/** Return JSON-decoded request body (empty array if invalid/missing). */
	public function getJsonBody(): array {
		if ($this->jsonBody === null) {
			$this->jsonBody = json_decode(file_get_contents('php://input'), true) ?? [];
		}
		return $this->jsonBody;
	}

	/** True if running under CLI SAPI. */
	public function isCli(): bool {
		return \php_sapi_name() === 'cli';
	}

	/** Detect execution context. */
	public function getContext(): string {
		if ($this->isCli()) {
			if (isset($_SERVER['REQUEST_METHOD'])) {
				return self::CONTEXT_BUILTIN_SERVER;
			}
			if (getenv('CRON_JOB') || getenv('IS_CRON')) {
				return self::CONTEXT_CRON;
			}
			if (defined('PHPUNIT_COMPOSER_INSTALL') || getenv('TEST_ENV')) {
				return self::CONTEXT_TEST;
			}
			return self::CONTEXT_CLI;
		}

		$method = strtoupper($this->server['REQUEST_METHOD'] ?? '');

		if ($method === 'POST') {
			if (!empty($this->files)) {
				return self::CONTEXT_WEB_UPLOAD;
			}
			return self::CONTEXT_WEB_POST;
		}

		if (
			isset($this->server['HTTP_X_REQUESTED_WITH']) &&
			strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
		) {
			return self::CONTEXT_WEB_AJAX;
		}

		if (
			isset($this->server['HTTP_ACCEPT']) &&
			str_contains($this->server['HTTP_ACCEPT'], 'application/json')
		) {
			return self::CONTEXT_WEB_API;
		}

		return self::CONTEXT_WEB_GET;
	}
}

