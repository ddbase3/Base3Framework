<?php declare(strict_types=1);

namespace Base3\Configuration\Api;

/**
 * Interface IConfiguration
 *
 * Backwards compatible config access:
 * - get/set/save remain unchanged
 * - additional convenience methods for practical usage
 *
 * Terminology:
 * - "group" == INI section / namespace
 * - "key"   == entry name within a group
 */
interface IConfiguration {

	/**
	 * Retrieves configuration data.
	 *
	 * If $configuration is an empty string, the full configuration is returned.
	 * Otherwise it returns the group/section with that name (or null).
	 *
	 * @param string $configuration group/section name
	 * @return mixed
	 */
	public function get($configuration = "");

	/**
	 * Sets configuration data.
	 *
	 * If $configuration is empty, the root configuration is replaced.
	 * Otherwise the given group/section is replaced.
	 *
	 * @param mixed $data
	 * @param string $configuration group/section name
	 * @return void
	 */
	public function set($data, $configuration = "");

	/**
	 * Saves the current configuration state (e.g. to file or database).
	 *
	 * Keep this method for BC, even if implementations return bool internally.
	 *
	 * @return void
	 */
	public function save();

	// ---------------------------------------------------------------------
	// Convenience API (new)
	// ---------------------------------------------------------------------

	/**
	 * Returns a group/section as array. If missing, returns $default.
	 *
	 * @param string $group
	 * @param array $default
	 * @return array
	 */
	public function getGroup(string $group, array $default = []): array;

	/**
	 * Returns a single value from a group, with optional default.
	 *
	 * @param string $group
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getValue(string $group, string $key, $default = null);

	/**
	 * Typed getters with default (very common in real usage).
	 */
	public function getString(string $group, string $key, string $default = ''): string;
	public function getInt(string $group, string $key, int $default = 0): int;
	public function getFloat(string $group, string $key, float $default = 0.0): float;
	public function getBool(string $group, string $key, bool $default = false): bool;

	/**
	 * Returns an array value. If stored as JSON string, implementation may decode.
	 *
	 * @param string $group
	 * @param string $key
	 * @param array $default
	 * @return array
	 */
	public function getArray(string $group, string $key, array $default = []): array;

	/**
	 * Existence checks.
	 */
	public function hasGroup(string $group): bool;
	public function hasValue(string $group, string $key): bool;

	/**
	 * Sets one value inside a group (should mark config dirty).
	 *
	 * @param string $group
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function setValue(string $group, string $key, $value): void;

	/**
	 * Replace/merge a group.
	 *
	 * @param string $group
	 * @param array $values
	 * @param bool $merge If true, merge into existing group; otherwise replace.
	 * @return void
	 */
	public function setGroup(string $group, array $values, bool $merge = true): void;

	/**
	 * Batch setter (multiple groups/keys).
	 *
	 * Example:
	 * [
	 *   'database' => ['host' => '...', 'name' => '...'],
	 *   'manager'  => ['layout' => 'simple']
	 * ]
	 *
	 * @param array $data
	 * @param bool $merge
	 * @return void
	 */
	public function setMany(array $data, bool $merge = true): void;

	/**
	 * Removes a group or a single key.
	 */
	public function removeGroup(string $group): void;
	public function removeValue(string $group, string $key): void;

	/**
	 * Save ergonomics:
	 * - saveIfDirty(): avoid unnecessary disk/db writes
	 * - trySave(): return bool without breaking the old save() signature
	 */
	public function isDirty(): bool;
	public function saveIfDirty(): bool;
	public function trySave(): bool;

	/**
	 * Reload underlying storage (file/db). Useful for long-running workers.
	 */
	public function reload(): void;

	/**
	 * Optional: Persist a single value immediately if backend supports it.
	 * - DB impl: can update one row
	 * - INI impl: may fallback to setValue() + saveIfDirty()
	 */
	public function persistValue(string $group, string $key, $value): bool;
}
