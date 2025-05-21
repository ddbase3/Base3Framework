<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IRequest;

class Request implements IRequest
{
	protected array $get;
	protected array $post;
	protected array $cookie;
	protected array $session;
	protected array $server;
	protected array $files;

	public function __construct(
		array $get = null,
		array $post = null,
		array $cookie = null,
		array $session = null,
		array $server = null,
		array $files = null
	) {
		$this->get = $get ?? $_GET;
		$this->post = $post ?? $_POST;
		$this->cookie = $cookie ?? $_COOKIE;
		$this->session = $session ?? ($_SESSION ?? []);
		$this->server = $server ?? $_SERVER;
		$this->files = $files ?? $_FILES;

		if ($this->isCli()) {
			$this->parseCliArgs();
		}
	}

	public static function fromGlobals(): self {
		return new self(
			$_GET,
			$_POST,
			$_COOKIE,
			$_SESSION ?? [],
			$_SERVER,
			$_FILES
		);
	}

	protected function parseCliArgs(): void {
		$args = $_SERVER['argv'] ?? [];
		array_shift($args); // remove script name

		foreach ($args as $arg) {
			if (preg_match('/^--([^=]+)=(.*)$/', $arg, $matches)) {
				$key = $matches[1];
				$value = $matches[2];
				$this->get[$key] = $value;
			} elseif (preg_match('/^--([^=]+)$/', $arg, $matches)) {
				$key = $matches[1];
				$this->get[$key] = true;
			}
		}
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
		return $this->get;
	}

	public function allPost(): array {
		return $this->post;
	}

	public function allCookie(): array {
		return $this->cookie;
	}

	public function allSession(): array {
		return $this->session;
	}

	public function allServer(): array {
		return $this->server;
	}

	public function allFiles(): array {
		return $this->files;
	}

	public function isCli(): bool {
		return \php_sapi_name() === 'cli';
	}

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
			strpos($this->server['HTTP_ACCEPT'], 'application/json') !== false
		) {
			return self::CONTEXT_WEB_API;
		}

		return self::CONTEXT_WEB_GET;
	}
}

