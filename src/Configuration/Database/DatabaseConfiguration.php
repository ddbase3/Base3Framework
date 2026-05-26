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

namespace Base3\Configuration\Database;

use Base3\Configuration\AbstractConfiguration;
use Base3\Database\Api\IDatabase;
use JsonException;
use RuntimeException;

/**
 * Class DatabaseConfiguration
 *
 * Database-backed configuration implementation.
 *
 * Storage model:
 * - group => logical configuration group
 * - name  => configuration key within the group
 * - type  => persisted value type
 * - value => scalar value or JSON-encoded array/object
 *
 * Supported value types:
 * - string
 * - int
 * - float
 * - bool
 * - array
 * - null
 */
class DatabaseConfiguration extends AbstractConfiguration {

	private const TABLE_NAME = 'base3_configuration';

	private IDatabase $database;

	private bool $tableEnsured = false;

	public function __construct(IDatabase $database) {
		$this->database = $database;
	}

	// ---------------------------------------------------------------------
	// AbstractConfiguration
	// ---------------------------------------------------------------------

	protected function load(): array {
		return $this->fetchConfigurationFromDatabase();
	}

	protected function saveData(array $data): bool {
		$this->ensureTableExists();

		$desiredKeys = [];

		foreach ($data as $group => $entries) {
			if (!is_string($group) || !is_array($entries)) continue;

			$this->assertValidKey($group, 'group');

			foreach ($entries as $name => $value) {
				$name = (string)$name;
				$this->assertValidKey($name, 'name');

				if (!isset($desiredKeys[$group])) $desiredKeys[$group] = [];
				$desiredKeys[$group][$name] = true;

				$this->insertConfigValue($group, $name, $value);
			}
		}

		$this->deleteObsoleteValues($desiredKeys);

		return true;
	}

	public function reload(): void {
		$this->cnf = null;
		$this->dirty = false;
		$this->ensureLoaded();
	}

	public function persistValue(string $group, string $key, $value): bool {
		$this->ensureLoaded();
		$this->setValue($group, $key, $value);

		$this->ensureTableExists();
		$this->insertConfigValue($group, $key, $value);

		$this->dirty = false;
		return true;
	}

	// ---------------------------------------------------------------------
	// DB loading/persistence
	// ---------------------------------------------------------------------

	private function fetchConfigurationFromDatabase(): array {
		$this->ensureTableExists();

		$query = "
			SELECT `group`, `name`, `type`, `value`
			FROM `" . self::TABLE_NAME . "`
			ORDER BY `group` ASC, `name` ASC
		";

		$rows = $this->database->multiQuery($query);
		$this->assertNoError('Failed to load configuration.');

		if (!is_array($rows) || $rows === []) {
			return [];
		}

		$config = [];

		foreach ($rows as $row) {
			if (!is_array($row) || !isset($row['group']) || !isset($row['name'])) {
				throw new RuntimeException('Invalid database row while loading configuration.');
			}

			$group = (string)$row['group'];
			$name = (string)$row['name'];
			$type = isset($row['type']) ? (string)$row['type'] : 'string';
			$value = $this->decodeConfigValue($group, $name, $type, $row['value'] ?? '');

			if ($group === '' || $name === '') {
				throw new RuntimeException('Invalid empty group or name while loading configuration.');
			}

			if (!isset($config[$group]) || !is_array($config[$group])) {
				$config[$group] = [];
			}

			$config[$group][$name] = $value;
		}

		return $config;
	}

	private function insertConfigValue(string $group, string $name, $value): void {
		$this->assertValidKey($group, 'group');
		$this->assertValidKey($name, 'name');

		[$type, $encodedValue] = $this->encodeConfigValue($group, $name, $value);

		$groupEscaped = $this->database->escape($group);
		$nameEscaped = $this->database->escape($name);
		$typeEscaped = $this->database->escape($type);
		$valueEscaped = $this->database->escape($encodedValue);

		$query = "
			INSERT INTO `" . self::TABLE_NAME . "` (`group`, `name`, `type`, `value`)
			VALUES (
				'" . $groupEscaped . "',
				'" . $nameEscaped . "',
				'" . $typeEscaped . "',
				'" . $valueEscaped . "'
			)
			ON DUPLICATE KEY UPDATE
				`type` = '" . $typeEscaped . "',
				`value` = '" . $valueEscaped . "'
		";

		$this->database->nonQuery($query);
		$this->assertNoError('Failed to persist configuration value "' . $group . '/' . $name . '".');
	}

	private function deleteObsoleteValues(array $desiredKeys): void {
		$query = "
			SELECT `group`, `name`
			FROM `" . self::TABLE_NAME . "`
		";

		$rows = $this->database->multiQuery($query);
		$this->assertNoError('Failed to load existing configuration keys.');

		if (!is_array($rows) || $rows === []) {
			return;
		}

		foreach ($rows as $row) {
			if (!is_array($row) || !isset($row['group']) || !isset($row['name'])) {
				throw new RuntimeException('Invalid database row while synchronizing configuration keys.');
			}

			$group = (string)$row['group'];
			$name = (string)$row['name'];

			if (isset($desiredKeys[$group][$name])) {
				continue;
			}

			$this->deleteConfigValue($group, $name);
		}
	}

	private function deleteConfigValue(string $group, string $name): void {
		$this->assertValidKey($group, 'group');
		$this->assertValidKey($name, 'name');

		$groupEscaped = $this->database->escape($group);
		$nameEscaped = $this->database->escape($name);

		$query = "
			DELETE FROM `" . self::TABLE_NAME . "`
			WHERE `group` = '" . $groupEscaped . "'
			  AND `name` = '" . $nameEscaped . "'
		";

		$this->database->nonQuery($query);
		$this->assertNoError('Failed to delete configuration value "' . $group . '/' . $name . '".');
	}

	// ---------------------------------------------------------------------
	// Table handling
	// ---------------------------------------------------------------------

	private function ensureTableExists(bool $force = false): void {
		if ($this->tableEnsured && !$force) {
			return;
		}

		$this->ensureDatabaseConnected();

		$query = "
			CREATE TABLE IF NOT EXISTS `" . self::TABLE_NAME . "` (
				`group` VARCHAR(190) NOT NULL,
				`name` VARCHAR(190) NOT NULL,
				`type` VARCHAR(20) NOT NULL DEFAULT 'string',
				`value` LONGTEXT NOT NULL,
				PRIMARY KEY (`group`, `name`)
			)
		";

		$this->database->nonQuery($query);
		$this->assertNoError('Failed to ensure configuration table exists.');

		$this->tableEnsured = true;
	}

	private function ensureDatabaseConnected(): void {
		$this->database->connect();
		$this->assertNoError('Failed to connect to database.');

		if (!$this->database->connected()) {
			throw new RuntimeException('Failed to connect to database.');
		}
	}

	// ---------------------------------------------------------------------
	// Value encoding/decoding
	// ---------------------------------------------------------------------

	private function encodeConfigValue(string $group, string $name, $value): array {
		if ($value === null) return ['null', ''];
		if (is_bool($value)) return ['bool', $value ? '1' : '0'];
		if (is_int($value)) return ['int', (string)$value];
		if (is_float($value)) return ['float', (string)$value];

		if (is_array($value) || is_object($value)) {
			try {
				return [
					'array',
					json_encode(
						$value,
						JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
					)
				];
			}
			catch (JsonException $e) {
				throw new RuntimeException(
					'Failed to encode configuration value "' . $group . '/' . $name . '": ' . $e->getMessage(),
					0,
					$e
				);
			}
		}

		return ['string', (string)$value];
	}

	private function decodeConfigValue(string $group, string $name, string $type, ?string $value) {
		$type = strtolower(trim($type));
		$value ??= '';

		return match ($type) {
			'null' => null,
			'bool' => $this->decodeBoolValue($value),
			'int' => (int)$value,
			'float' => (float)$value,
			'array' => $this->decodeArrayValue($group, $name, $value),
			default => $value
		};
	}

	private function decodeBoolValue(string $value): bool {
		$value = strtolower(trim($value));

		if ($value === '1' || $value === 'true' || $value === 'yes' || $value === 'on') {
			return true;
		}

		return false;
	}

	private function decodeArrayValue(string $group, string $name, string $value): array {
		$value = trim($value);

		if ($value === '') {
			return [];
		}

		try {
			$decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
		}
		catch (JsonException $e) {
			throw new RuntimeException(
				'Failed to decode configuration value "' . $group . '/' . $name . '": ' . $e->getMessage(),
				0,
				$e
			);
		}

		if (!is_array($decoded)) {
			throw new RuntimeException(
				'Configuration value "' . $group . '/' . $name . '" must decode to an array.'
			);
		}

		return $decoded;
	}

	// ---------------------------------------------------------------------
	// Validation/error handling
	// ---------------------------------------------------------------------

	private function assertValidKey(string $value, string $label): void {
		if ($value === '') {
			throw new RuntimeException('Configuration ' . $label . ' must not be empty.');
		}
	}

	private function assertNoError(string $message): void {
		if (!$this->database->isError()) {
			return;
		}

		$details = $this->database->errorMessage();
		$code = $this->database->errorNumber();

		if ($details !== '') {
			$message .= ' [' . $code . '] ' . $details;
		}

		throw new RuntimeException($message);
	}
}
