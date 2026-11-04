<?php declare(strict_types=1);

namespace Base3\Test\Migration\Database;

use Base3\Migration\Database\DatabaseMigrationRunner;
use Base3\Migration\Exception\MigrationException;
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

require_once __DIR__ . '/MigrationStepFixture.php';
require_once __DIR__ . '/MigrationProviderFixture.php';
require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../../Support/StaticClassMapStub.php';
