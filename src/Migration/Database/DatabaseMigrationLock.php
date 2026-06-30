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
 * Provides a connection-scoped database lock for migration execution.
 *
 * The lock uses MySQL/MariaDB named locks with timeout 0. It never waits.
 * If another process is already migrating, acquire() returns false
 * immediately. The lock is released explicitly or automatically when the
 * database connection closes.
 */
final class DatabaseMigrationLock {

	private ?string $lockName = null;

	public function __construct(
		private readonly IDatabase $database,
		private readonly int $ttlSeconds = 0
	) {}

	public function acquire(string $name): bool {
		$this->database->connect();

		if (!$this->database->connected()) {
			throw new MigrationException('Database connection could not be established for migration locking.');
		}

		$lockName = $this->createLockName($name);
		$result = $this->database->scalarQuery(
			"SELECT GET_LOCK(" . $this->quote($lockName) . ", 0)"
		);
		$this->assertNoError('Could not acquire migration lock.');

		if ((string) $result !== '1') {
			return false;
		}

		$this->lockName = $lockName;
		return true;
	}

	public function release(string $name): void {
		if ($this->lockName === null) {
			return;
		}

		$this->database->scalarQuery(
			"SELECT RELEASE_LOCK(" . $this->quote($this->lockName) . ")"
		);
		$this->assertNoError('Could not release migration lock.');

		$this->lockName = null;
	}

	private function createLockName(string $name): string {
		return 'base3.' . $name;
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
