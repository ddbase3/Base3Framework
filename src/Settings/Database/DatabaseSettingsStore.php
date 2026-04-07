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

namespace Base3\Settings\Database;

use Base3\Database\Api\IDatabase;
use Base3\Settings\Api\ISettingsStore;
use JsonException;
use RuntimeException;

/**
 * Class DatabaseSettingsStore
 *
 * Database-backed settings store using the table:
 * - base3_settingsstore
 *
 * Storage model:
 * - group   => logical settings group
 * - name    => dataset name within the group
 * - settings => JSON-encoded settings array
 *
 * This implementation is write-through:
 * - setSettings() persists immediately
 * - removeSettings() persists immediately
 * - save() is therefore a no-op
 */
class DatabaseSettingsStore implements ISettingsStore {

	private const TABLE_NAME = 'base3_settingsstore';

	private IDatabase $database;

	private bool $tableEnsured = false;

	public function __construct(IDatabase $database) {
		$this->database = $database;
		$this->reload();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSettings(string $group, string $name, array $default = []): array {
		$this->ensureTableExists();
		$this->assertValidKey($group, 'group');
		$this->assertValidKey($name, 'name');

		$groupEscaped = $this->database->escape($group);
		$nameEscaped = $this->database->escape($name);

		$query = "
			SELECT `settings`
			FROM `" . self::TABLE_NAME . "`
			WHERE `group` = '" . $groupEscaped . "'
			  AND `name` = '" . $nameEscaped . "'
			LIMIT 1
		";

		$row = $this->database->singleQuery($query);
		$this->assertNoError('Failed to load settings dataset.');

		if($row === null || !array_key_exists('settings', $row)) {
			return $default;
		}

		try {
			$settings = json_decode((string) $row['settings'], true, 512, JSON_THROW_ON_ERROR);
		}
		catch(JsonException $e) {
			throw new RuntimeException(
				'Failed to decode settings dataset "' . $group . '/' . $name . '": ' . $e->getMessage(),
				0,
				$e
			);
		}

		if(!is_array($settings)) {
			throw new RuntimeException(
				'Settings dataset "' . $group . '/' . $name . '" must decode to an array.'
			);
		}

		return $settings;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setSettings(string $group, string $name, array $settings): void {
		$this->ensureTableExists();
		$this->assertValidKey($group, 'group');
		$this->assertValidKey($name, 'name');

		try {
			$json = json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
		}
		catch(JsonException $e) {
			throw new RuntimeException(
				'Failed to encode settings dataset "' . $group . '/' . $name . '": ' . $e->getMessage(),
				0,
				$e
			);
		}

		$groupEscaped = $this->database->escape($group);
		$nameEscaped = $this->database->escape($name);
		$jsonEscaped = $this->database->escape($json);

		$query = "
			INSERT INTO `" . self::TABLE_NAME . "` (`group`, `name`, `settings`)
			VALUES ('" . $groupEscaped . "', '" . $nameEscaped . "', '" . $jsonEscaped . "')
			ON DUPLICATE KEY UPDATE `settings` = '" . $jsonEscaped . "'
		";

		$this->database->nonQuery($query);
		$this->assertNoError('Failed to persist settings dataset.');
	}

	/**
	 * {@inheritDoc}
	 */
	public function hasSettings(string $group, string $name): bool {
		$this->ensureTableExists();
		$this->assertValidKey($group, 'group');
		$this->assertValidKey($name, 'name');

		$groupEscaped = $this->database->escape($group);
		$nameEscaped = $this->database->escape($name);

		$query = "
			SELECT 1
			FROM `" . self::TABLE_NAME . "`
			WHERE `group` = '" . $groupEscaped . "'
			  AND `name` = '" . $nameEscaped . "'
			LIMIT 1
		";

		$result = $this->database->scalarQuery($query);
		$this->assertNoError('Failed to check settings dataset existence.');

		return $result !== null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function removeSettings(string $group, string $name): void {
		$this->ensureTableExists();
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
		$this->assertNoError('Failed to remove settings dataset.');
	}

	/**
	 * {@inheritDoc}
	 */
	public function save() {
		/*
		 * This implementation writes changes immediately.
		 * There is no deferred in-memory state to flush here.
		 */
	}

	/**
	 * {@inheritDoc}
	 */
	public function reload(): void {
		$this->database->connect();
		$this->assertNoError('Failed to connect to database.');
		$this->ensureTableExists(true);
	}

	/**
	 * Ensures that the backing table exists.
	 *
	 * @param bool $force
	 * @return void
	 */
	private function ensureTableExists(bool $force = false): void {
		if($this->tableEnsured && !$force) {
			return;
		}

		$this->database->connect();
		$this->assertNoError('Failed to connect to database.');

		$query = "
			CREATE TABLE IF NOT EXISTS `" . self::TABLE_NAME . "` (
				`group` VARCHAR(190) NOT NULL,
				`name` VARCHAR(190) NOT NULL,
				`settings` LONGTEXT NOT NULL,
				PRIMARY KEY (`group`, `name`)
			)
		";

		$this->database->nonQuery($query);
		$this->assertNoError('Failed to ensure settings store table exists.');

		$this->tableEnsured = true;
	}

	/**
	 * Validates a group or name key.
	 *
	 * @param string $value
	 * @param string $label
	 * @return void
	 */
	private function assertValidKey(string $value, string $label): void {
		if($value === '') {
			throw new RuntimeException('Settings ' . $label . ' must not be empty.');
		}
	}

	/**
	 * Throws an exception if the database reports an error state.
	 *
	 * @param string $message
	 * @return void
	 */
	private function assertNoError(string $message): void {
		if(!$this->database->isError()) {
			return;
		}

		$details = $this->database->errorMessage();
		$code = $this->database->errorNumber();

		if($details !== '') {
			$message .= ' [' . $code . '] ' . $details;
		}

		throw new RuntimeException($message);
	}
}
