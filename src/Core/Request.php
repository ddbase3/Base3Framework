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

	public function __construct() {
		// intentionally empty for lazy loading
	}

	public function initFromGlobals(): void {
		$this->get = $_GET;
		$this->post = $_POST;
		$this->cookie = $_COOKIE;
		$this->session = $_SESSION ?? [];
		$this->server = $_SERVER;
		$this->files = $_FILES;

		if ($this->isCli()) $this->parseCliArgs();
	}

	public static function fromGlobals(): self {
		$self = new self();
		$self->initFromGlobals();
		return $self;
	}

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

	protected function toArray(array|\ArrayAccess $source): array {
		if (is_array($source)) return $source;
		if ($source instanceof \Traversable) return iterator_to_array($source);
		$result = [];
		foreach ($source as $k => $v) $result[$k] = $v;
		return $result;
	}

	public function get(string $key, $default = null) {
		return $this->get[$key] ?? $default;
	}

	public function post(string $key, $default = null) {
		return $this->post[$key] ?? $default;
	}

	public function cookie(string $key, $default = null) {
		return $this->cookie[$key] ?? $default;
	}

	public function session(string $key, $default = null) {
		return $this->session[$key] ?? $default;
	}

	public function server(string $key, $default = null) {
		return $this->server[$key] ?? $default;
	}

	public function files(string $key, $default = null) {
		return $this->files[$key] ?? $default;
	}

	public function allGet(): array {
		return $this->toArray($this->get);
	}

	public function allPost(): array {
		return $this->toArray($this->post);
	}

	public function allCookie(): array {
		return $this->toArray($this->cookie);
	}

	public function allSession(): array {
		return $this->toArray($this->session);
	}

	public function allServer(): array {
		return $this->toArray($this->server);
	}

	public function allFiles(): array {
		return $this->toArray($this->files);
	}

	public function getJsonBody(): array {
		if ($this->jsonBody === null) {
			$this->jsonBody = json_decode(file_get_contents('php://input'), true) ?? [];
		}
		return $this->jsonBody;
	}

	public function isCli(): bool {
		return \php_sapi_name() === 'cli';
	}

	public function getContext(): string {
		if ($this->isCli()) {
			if (isset($_SERVER['REQUEST_METHOD'])) return self::CONTEXT_BUILTIN_SERVER;
			if (getenv('CRON_JOB') || getenv('IS_CRON')) return self::CONTEXT_CRON;
			if (defined('PHPUNIT_COMPOSER_INSTALL') || getenv('TEST_ENV')) return self::CONTEXT_TEST;
			return self::CONTEXT_CLI;
		}

		$method = strtoupper($this->server['REQUEST_METHOD'] ?? '');

		if ($method === 'POST') {
			if (!empty($this->files)) return self::CONTEXT_WEB_UPLOAD;
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
			strpos($this->server['HTTP_ACCEPT'], 'application/json') !== false
		) {
			return self::CONTEXT_WEB_API;
		}

		return self::CONTEXT_WEB_GET;
	}
}

