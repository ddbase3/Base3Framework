<?php declare(strict_types=1);

namespace Base3\Session\NoSession;

use Base3\Session\Api\ISession;

class NoSession implements ISession {

	public function started(): bool {
		return false;
	}

	public function getId(): string {
		return '';
	}

	public function start(): bool {
		return false;
	}

	public function destroy(): bool {
		return false;
	}

	public function get(string $key, mixed $default = null): mixed {
		return $default;
	}

	public function set(string $key, mixed $value): void {
		// no-op
	}

	public function has(string $key): bool {
		return false;
	}

	public function remove(string $key): void {
		// no-op
	}
}

