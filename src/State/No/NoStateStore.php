<?php declare(strict_types=1);

namespace Base3\State\No;

use Base3\State\Api\IStateStore;

/**
 * Class NoStateStore
 *
 * Null-object implementation of IStateStore.
 *
 * This implementation intentionally stores no data.
 * All write operations are no-ops, all read operations
 * behave as if the store is empty.
 *
 * Purpose:
 * - Safe default for dependency injection
 * - Explicitly disable runtime state
 * - Avoid null checks and conditional logic in services
 */
class NoStateStore implements IStateStore {

	public function get(string $key, mixed $default = null): mixed {
		return $default;
	}

	public function has(string $key): bool {
		return false;
	}

	public function set(string $key, mixed $value, ?int $ttlSeconds = null): void {
		// intentionally no-op
	}

	public function delete(string $key): bool {
		return false;
	}

	public function setIfNotExists(string $key, mixed $value, ?int $ttlSeconds = null): bool {
		// key is considered non-existent, but nothing is stored
		return true;
	}

	public function listKeys(string $prefix): array {
		return [];
	}

	public function flush(): void {
		// no-op
	}
}
