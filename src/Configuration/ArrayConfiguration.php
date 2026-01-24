<?php declare(strict_types=1);

namespace Base3\Configuration;

use Base3\Configuration\Api\IConfiguration;

/**
 * Class ArrayConfiguration
 *
 * Simple in-memory configuration implementation.
 * Intended for programmatic configuration (arrays) and small runtime overrides.
 */
class ArrayConfiguration implements IConfiguration {

	private array $data = [];
	private bool $dirty = false;

	public function __construct(array $initialData = []) {
		$this->data = $initialData;
	}

	// BC API
	public function get($configuration = "") {
		if ($configuration === "" || $configuration === null) return $this->data;
		return $this->data[$configuration] ?? null;
	}

	public function set($data, $configuration = "") {
		if ($configuration === "" || $configuration === null) {
			$this->data = is_array($data) ? $data : [];
			$this->dirty = true;
			return;
		}

		$this->data[$configuration] = $data;
		$this->dirty = true;
	}

	public function save() {
		$this->dirty = false;
	}

	// Convenience API
	public function getGroup(string $group, array $default = []): array {
		$val = $this->get($group);
		return is_array($val) ? $val : $default;
	}

	public function getValue(string $group, string $key, $default = null) {
		$g = $this->getGroup($group, []);
		return array_key_exists($key, $g) ? $g[$key] : $default;
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
		return is_array($this->get($group));
	}

	public function hasValue(string $group, string $key): bool {
		$g = $this->getGroup($group, []);
		return array_key_exists($key, $g);
	}

	public function setValue(string $group, string $key, $value): void {
		$g = $this->getGroup($group, []);
		$g[$key] = $value;
		$this->set($g, $group);
	}

	public function setGroup(string $group, array $values, bool $merge = true): void {
		if (!$merge) {
			$this->set($values, $group);
			return;
		}
		$current = $this->getGroup($group, []);
		$this->set(array_replace($current, $values), $group);
	}

	public function setMany(array $data, bool $merge = true): void {
		foreach ($data as $group => $values) {
			if (!is_string($group)) continue;

			if (!is_array($values)) {
				$this->set($values, $group);
				continue;
			}

			$this->setGroup($group, $values, $merge);
		}
	}

	public function removeGroup(string $group): void {
		if (!array_key_exists($group, $this->data)) return;
		unset($this->data[$group]);
		$this->dirty = true;
	}

	public function removeValue(string $group, string $key): void {
		$g = $this->getGroup($group, []);
		if (!array_key_exists($key, $g)) return;
		unset($g[$key]);
		$this->set($g, $group);
	}

	public function isDirty(): bool {
		return $this->dirty;
	}

	public function saveIfDirty(): bool {
		if (!$this->dirty) return true;
		return $this->trySave();
	}

	public function trySave(): bool {
		$this->save();
		return true;
	}

	public function reload(): void {
		$this->dirty = false;
	}

	public function persistValue(string $group, string $key, $value): bool {
		$this->setValue($group, $key, $value);
		return $this->saveIfDirty();
	}
}
