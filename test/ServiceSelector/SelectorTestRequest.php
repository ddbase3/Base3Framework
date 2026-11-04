<?php declare(strict_types=1);

namespace Base3\Test\ServiceSelector;

use Base3\Api\IRequest;

final class SelectorTestRequest implements IRequest {
	public function __construct(public array $values = []) {}
	public function get(string $key, $default = null) { return $this->values[$key] ?? $default; }
	public function post(string $key, $default = null) { return $default; }
	public function request(string $key, $default = null) { return $this->get($key, $default); }
	public function allRequest(): array { return $this->values; }
	public function cookie(string $key, $default = null) { return $default; }
	public function session(string $key, $default = null) { return $default; }
	public function server(string $key, $default = null) { return $default; }
	public function files(string $key, $default = null) { return $default; }
	public function allGet(): array { return $this->values; }
	public function allPost(): array { return []; }
	public function allCookie(): array { return []; }
	public function allSession(): array { return []; }
	public function allServer(): array { return []; }
	public function allFiles(): array { return []; }
	public function getJsonBody(): array { return []; }
	public function isCli(): bool { return false; }
	public function getContext(): string { return self::CONTEXT_TEST; }
}
