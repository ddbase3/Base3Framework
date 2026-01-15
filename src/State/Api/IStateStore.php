<?php declare(strict_types=1);

namespace Base3\State\Api;

/**
 * Interface IStateStore
 *
 * A persistent runtime state storage for services.
 *
 * Purpose:
 * --------
 * This interface is intended for storing *runtime state*, not configuration.
 *
 * Typical use-cases:
 * - Job scheduling (e.g. next run timestamps)
 * - Last processed IDs / cursors
 * - Locks / single-run guarantees
 * - Runtime flags or markers
 *
 * This is explicitly NOT:
 * - Application configuration (see IConfiguration)
 * - A service locator
 * - A pure cache (values may be persistent)
 *
 * Implementations decide:
 * - Where data is stored (file, database, memory, redis, ...)
 * - How values are serialized
 * - Whether writes are immediate or buffered
 */
interface IStateStore {

	/**
	 * Retrieves a value from the state store.
	 *
	 * Keys are expected to be fully qualified and namespaced
	 * by the consuming service.
	 *
	 * Recommended key format:
	 *     "<domain>.<service>.<name>"
	 *
	 * Examples:
	 * - "jobs.cleanup.nextrun"
	 * - "jobs.cleanup.lastrun"
	 * - "locks.jobs.cleanup"
	 *
	 * @param string $key
	 *        Fully qualified state key.
	 *
	 * @param mixed $default
	 *        Value returned if the key does not exist
	 *        or has expired.
	 *
	 * @return mixed
	 *         The stored value, or $default if not found.
	 */
	public function get(string $key, mixed $default = null): mixed;

	/**
	 * Checks whether a state entry exists and is not expired.
	 *
	 * @param string $key
	 *        Fully qualified state key.
	 *
	 * @return bool
	 *         True if the key exists and is valid, false otherwise.
	 */
	public function has(string $key): bool;

	/**
	 * Stores a value in the state store.
	 *
	 * The value may be of any type supported by the implementation.
	 * Implementations are responsible for serialization and persistence.
	 *
	 * @param string $key
	 *        Fully qualified state key.
	 *
	 * @param mixed $value
	 *        Arbitrary value to store.
	 *
	 * @param int|null $ttlSeconds
	 *        Optional time-to-live in seconds.
	 *        - null  → value does not expire
	 *        - > 0   → value expires after given seconds
	 *
	 * @return void
	 */
	public function set(string $key, mixed $value, ?int $ttlSeconds = null): void;

	/**
	 * Removes a value from the state store.
	 *
	 * @param string $key
	 *        Fully qualified state key.
	 *
	 * @return bool
	 *         True if the key existed and was removed,
	 *         false if the key did not exist.
	 */
	public function delete(string $key): bool;

	/**
	 * Atomically stores a value only if the key does not exist
	 * (or has already expired).
	 *
	 * This method is primarily intended for lock-like semantics,
	 * e.g. preventing concurrent execution of jobs.
	 *
	 * Example:
	 *     if (!$state->setIfNotExists('locks.jobs.cleanup', time(), 300)) {
	 *         return; // already running
	 *     }
	 *
	 * @param string $key
	 *        Fully qualified state key.
	 *
	 * @param mixed $value
	 *        Value to store if the key does not exist.
	 *
	 * @param int|null $ttlSeconds
	 *        Optional TTL.
	 *        Supplying a TTL is strongly recommended for locks
	 *        to avoid deadlocks.
	 *
	 * @return bool
	 *         True if the value was stored,
	 *         false if the key already existed.
	 */
	public function setIfNotExists(string $key, mixed $value, ?int $ttlSeconds = null): bool;

	/**
	 * Lists all keys starting with the given prefix.
	 *
	 * This method is intended for introspection, debugging,
	 * or small administrative tasks.
	 *
	 * Implementations may:
	 * - Return a best-effort result
	 * - Limit the number of returned keys
	 * - Not guarantee ordering
	 *
	 * It should NOT be used for large-scale iteration.
	 *
	 * @param string $prefix
	 *        Key prefix, e.g. "jobs.cleanup."
	 *
	 * @return string[]
	 *         List of matching keys.
	 */
	public function listKeys(string $prefix): array;

	/**
	 * Flushes buffered writes to the underlying storage.
	 *
	 * Some implementations (e.g. file-based) may buffer changes
	 * and write them in batches.
	 *
	 * Immediate backends (e.g. database, redis) may implement
	 * this as a no-op.
	 *
	 * @return void
	 */
	public function flush(): void;
}
