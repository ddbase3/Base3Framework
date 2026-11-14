<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Worker\Policy;

use Base3\CoreFoundation\State\Api\IStateStore;

final class PolicyMemoryStateStore implements IStateStore {

	public array $values = [];

	public function get(string $key, mixed $default = null): mixed { return $this->values[$key] ?? $default; }
	public function has(string $key): bool { return array_key_exists($key, $this->values); }
	public function set(string $key, mixed $value, ?int $ttlSeconds = null): void { $this->values[$key] = $value; }
	public function delete(string $key): bool {
		if (!$this->has($key)) return false;
		unset($this->values[$key]);
		return true;
	}
	public function setIfNotExists(string $key, mixed $value, ?int $ttlSeconds = null): bool {
		if ($this->has($key)) return false;
		$this->values[$key] = $value;
		return true;
	}
	public function listKeys(string $prefix): array {
		return array_values(array_filter(array_keys($this->values), static fn(string $key): bool => str_starts_with($key, $prefix)));
	}
	public function flush(): void {}
}
