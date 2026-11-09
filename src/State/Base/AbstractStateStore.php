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

namespace Base3\State\Base;

use Base3\State\Api\IStateStore;
use LogicException;

/**
 * Interface-complete base for persistent runtime state backends.
 *
 * Read defaults describe an empty store. Mutating operations that require
 * persistence or atomicity fail explicitly. This is not a replacement for the
 * intentionally inert NoStateStore implementation.
 */
abstract class AbstractStateStore implements IStateStore {

	/** @inheritDoc */
	public function get(string $key, mixed $default = null): mixed {
		return $default;
	}

	/** @inheritDoc */
	public function has(string $key): bool {
		return false;
	}

	/**
	 * A backend must implement persistent writes.
	 *
	 * @param string $key Fully qualified state key.
	 * @param mixed $value Value to store.
	 * @param int|null $ttlSeconds Optional time-to-live.
	 * @return void
	 * @throws LogicException Always, unless a backend overrides the method.
	 */
	public function set(string $key, mixed $value, ?int $ttlSeconds = null): void {
		throw new LogicException(static::class . ' does not implement set().');
	}

	/** @inheritDoc */
	public function delete(string $key): bool {
		return false;
	}

	/**
	 * A backend must implement atomic conditional writes before claiming a lock.
	 *
	 * @param string $key Fully qualified state key.
	 * @param mixed $value Value to store if absent.
	 * @param int|null $ttlSeconds Optional time-to-live.
	 * @return bool True only if the value was actually stored.
	 * @throws LogicException Always, unless a backend overrides the method.
	 */
	public function setIfNotExists(string $key, mixed $value, ?int $ttlSeconds = null): bool {
		throw new LogicException(static::class . ' does not implement setIfNotExists().');
	}

	/** @inheritDoc */
	public function listKeys(string $prefix): array {
		return [];
	}

	/** @inheritDoc */
	public function flush(): void {
		// This base has no buffered writes.
	}
}
