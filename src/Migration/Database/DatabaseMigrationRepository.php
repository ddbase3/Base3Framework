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

namespace Base3\Migration\Database;

use Base3\Database\Api\IDatabase;
use Base3\Migration\Exception\MigrationException;

/**
 * Stores the migration history in the project database.
 */
final class DatabaseMigrationRepository {

	private bool $initialized = false;

	public function __construct(
		private readonly IDatabase $database
	) {}

	public function ensureReady(): void {
		$this->database->connect();

		if (!$this->database->connected()) {
			throw new MigrationException('Database connection could not be established for migrations.');
		}

		if ($this->initialized) {
			return;
		}

		$this->ensureTable();
		$this->initialized = true;
	}

	public function getApplied(string $provider, string $migration): ?array {
		$this->ensureReady();

		$row = $this->database->singleQuery(
			"SELECT `provider`, `migration`, `version`, `checksum`, `applied_at`, `execution_ms`
			 FROM `base3_migrations`
			 WHERE `provider` = " . $this->quote($provider) . "
			 AND `migration` = " . $this->quote($migration) . "
			 LIMIT 1"
		);

		$this->assertNoError('Could not read migration state.');

		return is_array($row) ? $row : null;
	}

	public function markApplied(string $provider, string $migration, string $version, string $checksum, int $executionMs): void {
		$this->ensureReady();

		$this->database->nonQuery(
			"INSERT INTO `base3_migrations`
			 (`provider`, `migration`, `version`, `checksum`, `applied_at`, `execution_ms`)
			 VALUES (
				" . $this->quote($provider) . ",
				" . $this->quote($migration) . ",
				" . $this->quote($version) . ",
				" . $this->quote($checksum) . ",
				" . $this->quote(date('Y-m-d H:i:s')) . ",
				" . $executionMs . "
			 )"
		);

		$this->assertNoError('Could not store migration state.');
	}

	private function ensureTable(): void {
		$this->database->nonQuery(
			"CREATE TABLE IF NOT EXISTS `base3_migrations` (
				`provider` VARCHAR(190) NOT NULL,
				`migration` VARCHAR(190) NOT NULL,
				`version` VARCHAR(64) NOT NULL,
				`checksum` CHAR(64) NOT NULL,
				`applied_at` DATETIME NOT NULL,
				`execution_ms` INT NOT NULL DEFAULT 0,
				PRIMARY KEY (`provider`, `migration`),
				KEY `idx_provider_version` (`provider`, `version`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
		);

		$this->assertNoError('Could not create migration table.');
	}

	private function quote(string $value): string {
		return "'" . $this->database->escape($value) . "'";
	}

	private function assertNoError(string $message): void {
		if (!$this->database->isError()) {
			return;
		}

		throw new MigrationException($message . ' ' . $this->database->errorMessage());
	}
}
