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

namespace Base3\Test\Migration\Database;

use Base3\Core\Migration\Database\DatabaseMigrationRunner;
use Base3\CoreFoundation\Migration\Exception\MigrationException;
use Base3\Test\Support\RecordingDatabaseStub;
use Base3\Test\Support\StaticClassMapStub;
use PHPUnit\Framework\TestCase;

final class DatabaseMigrationRunnerTest extends TestCase {

	private function setupRunner(array $steps, bool $active = true, bool $busy = false, bool $failOnBusy = false): array {
		$db = new RecordingDatabaseStub();
		$db->scalarValue = $busy ? 0 : 1;
		$classmap = new StaticClassMapStub();
		$provider = new MigrationProviderFixture($steps, $active);
		$classmap->register('fixture', $provider);
		return [new DatabaseMigrationRunner($classmap, $db, null, null, $failOnBusy), $db, $provider];
	}

	public function testActiveProviderExecutesAndRecordsMigration(): void {
		$step = new MigrationStepFixture('1');
		[$runner, $db] = $this->setupRunner([$step]);
		$runner->migrate();
		$this->assertSame(1, $step->calls);
		$this->assertCount(2, $db->scalarQueries);
		$this->assertStringContainsString('GET_LOCK', $db->scalarQueries[0]);
		$this->assertStringContainsString('RELEASE_LOCK', $db->scalarQueries[1]);
		$this->assertCount(2, $db->nonQueries);
		$this->assertStringContainsString('INSERT INTO `base3_migrations`', $db->nonQueries[1]);
	}

	public function testInactiveProviderDoesNotRunItsMigrations(): void {
		$step = new MigrationStepFixture('1');
		[$runner, $db] = $this->setupRunner([$step],false);
		$runner->migrate();
		$this->assertSame(0, $step->calls);
		$this->assertCount(1, $db->nonQueries);
		$this->assertCount(2, $db->scalarQueries);
	}

	public function testBusyLockSkipsAllMigrationsWhenConfiguredToWaitForOtherRunner(): void {
		$step = new MigrationStepFixture('1');
		[$runner, $db] = $this->setupRunner([$step],true,true);
		$runner->migrate();
		$this->assertSame(0, $step->calls);
		$this->assertCount(1, $db->scalarQueries);
	}

	public function testBusyLockCanFailClosedWhenConfigured(): void {
		[$runner, $db] = $this->setupRunner([],true,true,true);
		$this->expectException(MigrationException::class);
		$runner->migrate();
	}

	public function testInvalidProviderItemsCannotBecomeMigrations(): void {
		[$runner, $db] = $this->setupRunner([false]);
		try { $runner->migrate(); $this->fail('Invalid migration item was accepted.'); }
		catch (MigrationException $error) { $this->assertStringContainsString('instances or class names', $error->getMessage()); }
		$this->assertCount(2, $db->scalarQueries);
	}

	public function testUnknownMigrationClassDoesNotRun(): void {
		[$runner, $db] = $this->setupRunner(['invalid/migration']);
		try { $runner->migrate(); $this->fail('Unknown migration class was accepted.'); }
		catch (MigrationException $error) { $this->assertStringContainsString('does not implement', $error->getMessage()); }
		$this->assertCount(2, $db->scalarQueries);
	}

	public function testAlreadyAppliedMigrationWithMatchingChecksumIsNotRepeated(): void {
		$step = new MigrationStepFixture('1');
		[$runner, $db] = $this->setupRunner([$step]);
		$db->singleRow = ['checksum'=>hash_file('sha256',__DIR__ . '/MigrationStepFixture.php')];
		$runner->migrate();
		$this->assertSame(0, $step->calls);
		$this->assertCount(1, $db->nonQueries);
	}

	public function testChecksumDriftStopsExecutionWithoutWritingNewHistory(): void {
		$step = new MigrationStepFixture('1');
		[$runner, $db] = $this->setupRunner([$step]);
		$db->singleRow = ['checksum'=>'tampered'];
		try { $runner->migrate(); $this->fail('Changed migration was accepted.'); }
		catch (MigrationException $error) { $this->assertStringContainsString('checksum mismatch', $error->getMessage()); }
		$this->assertSame(0, $step->calls);
		$this->assertCount(1, $db->nonQueries);
		$this->assertCount(2, $db->scalarQueries);
	}

	public function testMarkedAppliedMigrationSkipsOnlyTheChecksumComparison(): void {
		$step = new LegacyMigrationStepFixture();
		[$runner, $db] = $this->setupRunner([$step]);
		$db->singleRow = ['checksum' => 'previously-applied-file-hash'];
		$runner->migrate();
		$this->assertSame(0, $step->calls);
		$this->assertCount(1, $db->singleQueries);
		$this->assertCount(1, $db->nonQueries);
		$this->assertCount(2, $db->scalarQueries);
	}

	public function testMarkedPendingMigrationStillRunsAndRecordsItsSourceChecksum(): void {
		$step = new LegacyMigrationStepFixture();
		[$runner, $db] = $this->setupRunner([$step]);
		$runner->migrate();
		$this->assertSame(1, $step->calls);
		$this->assertCount(2, $db->nonQueries);
		$this->assertStringContainsString(
			hash_file('sha256', __DIR__ . '/LegacyMigrationStepFixture.php'),
			$db->nonQueries[1]
		);
	}

	public function testLegacyMarkerWithoutMigrationContractIsRejected(): void {
		[$runner, $db] = $this->setupRunner([new LegacyMarkerOnlyFixture()]);
		$this->expectException(MigrationException::class);
		$runner->migrate();
	}

	public function testLegacyMarkerDoesNotChangeMigrationContract(): void {
		$this->assertFalse(is_subclass_of(\Base3\CoreFoundation\Migration\Api\ILegacyDatabaseMigration::class, \Base3\CoreFoundation\Migration\Api\IDatabaseMigration::class));
		$this->assertFalse(method_exists(\Base3\CoreFoundation\Migration\Api\IDatabaseMigration::class, 'down'));
	}

	public function testDatabaseErrorAfterMigrationPreventsAppliedMarker(): void {
		$step = new MigrationStepFixture('1');
		[$runner, $db] = $this->setupRunner([$step]);
		$step->database = $db;
		$step->triggerDatabaseFailure = true;
		try { $runner->migrate(); $this->fail('Failed migration was marked applied.'); }
		catch (MigrationException $error) { $this->assertStringContainsString('Migration failed', $error->getMessage()); }
		$this->assertSame(1, $step->calls);
		$this->assertCount(1, $db->nonQueries);
	}
}

require_once __DIR__ . '/LegacyMigrationStepFixture.php';
require_once __DIR__ . '/LegacyMarkerOnlyFixture.php';
require_once __DIR__ . '/MigrationStepFixture.php';
require_once __DIR__ . '/MigrationProviderFixture.php';
require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../../Support/StaticClassMapStub.php';
