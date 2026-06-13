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
