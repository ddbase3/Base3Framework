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

use Base3\Api\IClassMap;
use Base3\Database\Api\IDatabase;
use Base3\Migration\Api\IDatabaseMigration;
use Base3\Migration\Api\IDatabaseMigrationProvider;
use Base3\Migration\Api\IMigrationRunner;
use Base3\Migration\Exception\MigrationException;
use ReflectionClass;
use Throwable;

/**
 * Runs database migrations provided by active migration providers.
 */
final class DatabaseMigrationRunner implements IMigrationRunner {

	private const LOCK_NAME = 'database_migrations';

	private DatabaseMigrationRepository $repository;
	private DatabaseMigrationLock $lock;

	public function __construct(
		private readonly IClassMap $classMap,
		private readonly IDatabase $database,
		?DatabaseMigrationRepository $repository = null,
		?DatabaseMigrationLock $lock = null,
		private readonly bool $failIfLockBusy = false
	) {
		$this->repository = $repository ?? new DatabaseMigrationRepository($database);
		$this->lock = $lock ?? new DatabaseMigrationLock($database);
	}

	public function migrate(): void {
		$this->repository->ensureReady();

		if (!$this->lock->acquire(self::LOCK_NAME)) {
			if ($this->failIfLockBusy) {
				throw new MigrationException('Database migrations are already running.');
			}

			return;
		}

		try {
			$this->runActiveProviders();
		} finally {
			$this->releaseLock();
		}
	}

	private function runActiveProviders(): void {
		$providers = $this->classMap->getInstancesByInterface(IDatabaseMigrationProvider::class);
		usort($providers, fn($a, $b) => strcmp($a::getName(), $b::getName()));

		foreach ($providers as $provider) {
			if (!$provider instanceof IDatabaseMigrationProvider) {
				continue;
			}

			if (!$provider->isActive()) {
				continue;
			}

			$this->runProvider($provider);
		}
	}

	private function runProvider(IDatabaseMigrationProvider $provider): void {
		$providerName = $provider::getName();
		$migrations = $this->normalizeMigrations($provider->getMigrations());

		usort($migrations, function(IDatabaseMigration $a, IDatabaseMigration $b): int {
			$versionCompare = strnatcmp($a->getVersion(), $b->getVersion());
			if ($versionCompare !== 0) {
				return $versionCompare;
			}

			return strcmp($a::getName(), $b::getName());
		});

		foreach ($migrations as $migration) {
			$this->runMigration($providerName, $migration);
		}
	}

	/**
	 * @param array<int, IDatabaseMigration|string> $items
	 * @return array<int, IDatabaseMigration>
	 */
	private function normalizeMigrations(array $items): array {
		$migrations = [];

		foreach ($items as $item) {
			if ($item instanceof IDatabaseMigration) {
				$migrations[] = $item;
				continue;
			}

			if (!is_string($item) || trim($item) === '') {
				throw new MigrationException('Migration providers must return migration instances or class names.');
			}

			$instance = $this->classMap->instantiate($item);
			if (!$instance instanceof IDatabaseMigration) {
				throw new MigrationException('Migration class does not implement IDatabaseMigration: ' . $item);
			}

			$migrations[] = $instance;
		}

		return $migrations;
	}

	private function runMigration(string $providerName, IDatabaseMigration $migration): void {
		$migrationName = $migration::getName();
		$checksum = $this->createChecksum($migration);
		$applied = $this->repository->getApplied($providerName, $migrationName);

		if ($applied !== null) {
			$this->assertChecksumMatches($providerName, $migration, $applied, $checksum);
			return;
		}

		$start = microtime(true);

		$migration->up();

		if ($this->database->isError()) {
			throw new MigrationException(
				'Migration failed: ' . $providerName . ' / ' . $migrationName . ' - ' . $this->database->errorMessage()
			);
		}

		$executionMs = (int) round((microtime(true) - $start) * 1000);
		$this->repository->markApplied(
			$providerName,
			$migrationName,
			$migration->getVersion(),
			$checksum,
			$executionMs
		);
	}

	private function assertChecksumMatches(string $providerName, IDatabaseMigration $migration, array $applied, string $checksum): void {
		$storedChecksum = (string) ($applied['checksum'] ?? '');
		if ($storedChecksum === $checksum) {
			return;
		}

		throw new MigrationException(
			'Migration checksum mismatch: ' . $providerName . ' / ' . $migration::getName()
		);
	}

	private function createChecksum(IDatabaseMigration $migration): string {
		$reflection = new ReflectionClass($migration);
		$file = $reflection->getFileName();

		if (is_string($file) && is_file($file)) {
			return hash_file('sha256', $file);
		}

		return hash(
			'sha256',
			get_class($migration) . '|' . $migration::getName() . '|' . $migration->getVersion() . '|' . $migration->getDescription()
		);
	}

	private function releaseLock(): void {
		try {
			$this->lock->release(self::LOCK_NAME);
		} catch (Throwable) {
			// TTL-based locks are eventually released by expiry.
		}
	}
}
