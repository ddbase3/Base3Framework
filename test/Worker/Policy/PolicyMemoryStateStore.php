<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy;

use Base3\State\Api\IStateStore;

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
