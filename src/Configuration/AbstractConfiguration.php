<?php declare(strict_types=1);

namespace Base3\Configuration;

use Base3\Configuration\Api\IConfiguration;

/**
 * Class AbstractConfiguration
 *
 * Shared in-memory implementation for IConfiguration.
 * Concrete implementations only need to implement load() and saveData().
 *
 * Notes:
 * - get/set/save keep BC semantics
 * - adds dirty tracking, typed getters, group/value helpers, reload
 * - persistValue() defaults to setValue()+saveIfDirty() but can be optimized in subclasses (DB)
 */
abstract class AbstractConfiguration implements IConfiguration {

	protected ?array $cnf = null;
	protected bool $dirty = false;

	/**
	 * Load configuration from storage.
	 *
	 * Must return a normalized array like:
	 * [
	 *   'group' => ['key' => 'value', ...],
	 *   ...
	 * ]
	 */
	abstract protected function load(): array;

	/**
	 * Save configuration to storage.
	 *
	 * Return true on success, false on failure.
	 */
	abstract protected function saveData(array $data): bool;

	// ---------------------------------------------------------------------
	// BC API
	// ---------------------------------------------------------------------

	public function get($configuration = "") {
		$this->ensureLoaded();
		if (!strlen((string)$configuration)) return $this->cnf;
		return $this->cnf[$configuration] ?? null;
	}

	public function set($data, $configuration = "") {
		$this->ensureLoaded();
		if (strlen((string)$configuration)) {
			$this->cnf[$configuration] = $data;
		} else {
			$this->cnf = $data;
		}
		$this->dirty = true;
	}

	public function save() {
		$this->trySave();
	}

	// ---------------------------------------------------------------------
	// Convenience API
	// ---------------------------------------------------------------------

	public function getGroup(string $group, array $default = []): array {
		$this->ensureLoaded();
		$g = $this->cnf[$group] ?? null;
		return is_array($g) ? $g : $default;
	}

	public function getValue(string $group, string $key, $default = null) {
		$this->ensureLoaded();
		if (!isset($this->cnf[$group]) || !is_array($this->cnf[$group])) return $default;
		return array_key_exists($key, $this->cnf[$group]) ? $this->cnf[$group][$key] : $default;
	}

	public function getString(string $group, string $key, string $default = ''): string {
		$val = $this->getValue($group, $key, $default);
		if ($val === null) return $default;
		if (is_string($val)) return $val;
		if (is_scalar($val)) return (string)$val;
		return $default;
	}

	public function getInt(string $group, string $key, int $default = 0): int {
		$val = $this->getValue($group, $key, null);
		if ($val === null) return $default;
		if (is_int($val)) return $val;
		if (is_bool($val)) return $val ? 1 : 0;
		if (is_numeric($val)) return (int)$val;
		return $default;
	}

	public function getFloat(string $group, string $key, float $default = 0.0): float {
		$val = $this->getValue($group, $key, null);
		if ($val === null) return $default;
		if (is_float($val)) return $val;
		if (is_int($val)) return (float)$val;
		if (is_numeric($val)) return (float)$val;
		return $default;
	}

	public function getBool(string $group, string $key, bool $default = false): bool {
		$val = $this->getValue($group, $key, null);
		if ($val === null) return $default;
		if (is_bool($val)) return $val;

		if (is_int($val)) return $val !== 0;
		if (is_float($val)) return $val !== 0.0;

		if (is_string($val)) {
			$v = strtolower(trim($val));
			if ($v === '1' || $v === 'true' || $v === 'yes' || $v === 'on') return true;
			if ($v === '0' || $v === 'false' || $v === 'no' || $v === 'off' || $v === '') return false;
		}

		return $default;
	}

	public function getArray(string $group, string $key, array $default = []): array {
		$val = $this->getValue($group, $key, null);
		if ($val === null) return $default;
		if (is_array($val)) return $val;

		// Optional convenience: allow JSON stored as string
		if (is_string($val)) {
			$trim = trim($val);
			if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
				$decoded = json_decode($trim, true);
				if (is_array($decoded)) return $decoded;
			}
		}

		return $default;
	}

	public function hasGroup(string $group): bool {
		$this->ensureLoaded();
		return isset($this->cnf[$group]) && is_array($this->cnf[$group]);
	}

	public function hasValue(string $group, string $key): bool {
		$this->ensureLoaded();
		return isset($this->cnf[$group]) && is_array($this->cnf[$group]) && array_key_exists($key, $this->cnf[$group]);
	}

	public function setValue(string $group, string $key, $value): void {
		$this->ensureLoaded();
		if (!isset($this->cnf[$group]) || !is_array($this->cnf[$group])) $this->cnf[$group] = [];
		$this->cnf[$group][$key] = $value;
		$this->dirty = true;
	}

	public function setGroup(string $group, array $values, bool $merge = true): void {
		$this->ensureLoaded();
		if (!$merge || !isset($this->cnf[$group]) || !is_array($this->cnf[$group])) {
			$this->cnf[$group] = $values;
		} else {
			$this->cnf[$group] = array_replace($this->cnf[$group], $values);
		}
		$this->dirty = true;
	}

	public function setMany(array $data, bool $merge = true): void {
		$this->ensureLoaded();
		foreach ($data as $group => $values) {
			if (!is_string($group)) continue;
			if (!is_array($values)) {
				// allow setMany(['foo' => 123]) to replace group
				$this->cnf[$group] = $values;
				$this->dirty = true;
				continue;
			}
			$this->setGroup($group, $values, $merge);
		}
	}

	public function removeGroup(string $group): void {
		$this->ensureLoaded();
		if (!array_key_exists($group, $this->cnf)) return;
		unset($this->cnf[$group]);
		$this->dirty = true;
	}

	public function removeValue(string $group, string $key): void {
		$this->ensureLoaded();
		if (!isset($this->cnf[$group]) || !is_array($this->cnf[$group])) return;
		if (!array_key_exists($key, $this->cnf[$group])) return;
		unset($this->cnf[$group][$key]);
		$this->dirty = true;
	}

	public function isDirty(): bool {
		return $this->dirty;
	}

	public function saveIfDirty(): bool {
		if (!$this->dirty) return true;
		return $this->trySave();
	}

	public function trySave(): bool {
		$this->ensureLoaded();
		$ok = $this->saveData($this->cnf ?? []);
		if ($ok) $this->dirty = false;
		return $ok;
	}

	public function reload(): void {
		$this->cnf = null;
		$this->dirty = false;
		$this->ensureLoaded();
	}

	public function persistValue(string $group, string $key, $value): bool {
		// Default fallback: update memory and save whole config
		$this->setValue($group, $key, $value);
		return $this->saveIfDirty();
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	protected function ensureLoaded(): void {
		if ($this->cnf !== null) return;
		$this->cnf = $this->normalize($this->load());
		$this->dirty = false;
	}

	protected function normalize(array $data): array {
		$out = [];
		foreach ($data as $group => $values) {
			if (!is_string($group)) continue;
			if (!is_array($values)) {
				$out[$group] = $values;
				continue;
			}
			$out[$group] = $values;
		}
		return $out;
	}
}
