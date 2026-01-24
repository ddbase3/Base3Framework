<?php declare(strict_types=1);

namespace Base3\Test\State;

use Base3\State\Api\IStateStore;

/**
 * Class StateStoreStub
 *
 * Simple, DI-free in-memory state store for unit tests.
 * Supports TTL expiry and basic lock-like semantics via setIfNotExists().
 */
class StateStoreStub implements IStateStore {

	/**
	 * @var array<string, array{value:mixed, expires_at:int|null}>
	 */
	private array $items = [];

	public function get(string $key, mixed $default = null): mixed {
		$this->purgeIfExpired($key);

		if (!array_key_exists($key, $this->items)) return $default;

		return $this->items[$key]['value'];
	}

	public function has(string $key): bool {
		$this->purgeIfExpired($key);
		return array_key_exists($key, $this->items);
	}

	public function set(string $key, mixed $value, ?int $ttlSeconds = null): void {
		$expiresAt = null;

		if ($ttlSeconds !== null) {
			// ttlSeconds <= 0 means "already expired" -> behave like delete
			if ($ttlSeconds <= 0) {
				$this->delete($key);
				return;
			}
			$expiresAt = time() + $ttlSeconds;
		}

		$this->items[$key] = [
			'value' => $value,
			'expires_at' => $expiresAt
		];
	}

	public function delete(string $key): bool {
		$this->purgeIfExpired($key);

		if (!array_key_exists($key, $this->items)) return false;

		unset($this->items[$key]);
		return true;
	}

	public function setIfNotExists(string $key, mixed $value, ?int $ttlSeconds = null): bool {
		$this->purgeIfExpired($key);

		if (array_key_exists($key, $this->items)) return false;

		$this->set($key, $value, $ttlSeconds);
		return true;
	}

	public function listKeys(string $prefix): array {
		$this->purgeExpiredByPrefix($prefix);

		$keys = [];
		foreach ($this->items as $k => $_) {
			if (str_starts_with($k, $prefix)) $keys[] = $k;
		}
		return $keys;
	}

	public function flush(): void {
		// no-op (in-memory)
	}

	// ---------------------------------------------------------------------
	// Test helpers (optional convenience)
	// ---------------------------------------------------------------------

	public function all(): array {
		$this->purgeExpiredAll();

		$out = [];
		foreach ($this->items as $k => $v) {
			$out[$k] = $v['value'];
		}
		return $out;
	}

	// ---------------------------------------------------------------------
	// Internal expiry handling
	// ---------------------------------------------------------------------

	private function purgeIfExpired(string $key): void {
		if (!array_key_exists($key, $this->items)) return;

		$expiresAt = $this->items[$key]['expires_at'];
		if ($expiresAt === null) return;

		if (time() >= $expiresAt) {
			unset($this->items[$key]);
		}
	}

	private function purgeExpiredAll(): void {
		foreach (array_keys($this->items) as $key) {
			$this->purgeIfExpired($key);
		}
	}

	private function purgeExpiredByPrefix(string $prefix): void {
		foreach (array_keys($this->items) as $key) {
			if (!str_starts_with($key, $prefix)) continue;
			$this->purgeIfExpired($key);
		}
	}
}
