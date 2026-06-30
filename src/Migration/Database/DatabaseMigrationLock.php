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
 * Provides a small database-backed lock for migration execution.
 */
final class DatabaseMigrationLock {

	private bool $initialized = false;
	private ?string $token = null;

	public function __construct(
		private readonly IDatabase $database,
		private readonly int $ttlSeconds = 300
	) {}

	public function acquire(string $name): bool {
		$this->ensureReady();

		$now = time();
		$expiresAt = $now + $this->ttlSeconds;
		$token = bin2hex(random_bytes(16));

		$this->database->nonQuery(
			"DELETE FROM `base3_migration_locks`
			 WHERE `expires_at` < " . $now
		);
		$this->assertNoError('Could not remove expired migration locks.');

		$this->database->nonQuery(
			"INSERT IGNORE INTO `base3_migration_locks`
			 (`name`, `token`, `acquired_at`, `expires_at`)
			 VALUES (
				" . $this->quote($name) . ",
				" . $this->quote($token) . ",
				" . $now . ",
				" . $expiresAt . "
			 )"
		);
		$this->assertNoError('Could not acquire migration lock.');

		if ($this->database->affectedRows() !== 1) {
			return false;
		}

		$this->token = $token;
		return true;
	}

	public function release(string $name): void {
		if ($this->token === null) {
			return;
		}

		$this->database->nonQuery(
			"DELETE FROM `base3_migration_locks`
			 WHERE `name` = " . $this->quote($name) . "
			 AND `token` = " . $this->quote($this->token)
		);

		$this->token = null;
	}

	private function ensureReady(): void {
		$this->database->connect();

		if (!$this->database->connected()) {
			throw new MigrationException('Database connection could not be established for migration locking.');
		}

		if ($this->initialized) {
			return;
		}

		$this->ensureTable();
		$this->initialized = true;
	}

	private function ensureTable(): void {
		$this->database->nonQuery(
			"CREATE TABLE IF NOT EXISTS `base3_migration_locks` (
				`name` VARCHAR(190) NOT NULL,
				`token` VARCHAR(64) NOT NULL,
				`acquired_at` INT NOT NULL,
				`expires_at` INT NOT NULL,
				PRIMARY KEY (`name`),
				KEY `idx_expires_at` (`expires_at`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
		);

		$this->assertNoError('Could not create migration lock table.');
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
