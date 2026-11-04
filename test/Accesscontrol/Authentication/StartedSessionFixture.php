<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use Base3\Session\Api\ISession;

/** Reports an active host-managed session without touching the PHP session runtime. */
final class StartedSessionFixture implements ISession {
	public function started(): bool { return true; }
	public function getId(): string { return 'fixture'; }
	public function start(): bool { return true; }
	public function destroy(): bool { return false; }
	public function get(string $key, mixed $default = null): mixed { return $default; }
	public function set(string $key, mixed $value): void {}
	public function has(string $key): bool { return false; }
	public function remove(string $key): void {}
}
