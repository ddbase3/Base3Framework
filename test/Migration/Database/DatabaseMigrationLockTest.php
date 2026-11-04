<?php declare(strict_types=1);

namespace Base3\Test\Migration\Database;

use Base3\Migration\Database\DatabaseMigrationLock;
use Base3\Migration\Exception\MigrationException;
use Base3\Test\Support\RecordingDatabaseStub;
use PHPUnit\Framework\TestCase;

final class DatabaseMigrationLockTest extends TestCase {

	public function testAcquisitionAndReleaseUseTheSameEscapedDatabaseLock(): void {
		$db = new RecordingDatabaseStub();
		$db->scalarValue = 1;
		$lock = new DatabaseMigrationLock($db);
		$this->assertTrue($lock->acquire("test'project"));
		$this->assertSame(1, $db->connectCalls);
		$this->assertStringContainsString('GET_LOCK(', $db->scalarQueries[0]);
		$this->assertStringContainsString("base3.test\\'project", $db->scalarQueries[0]);
		$lock->release('other-name');
		$this->assertStringContainsString('RELEASE_LOCK(', $db->scalarQueries[1]);
		$this->assertStringContainsString("base3.test\\'project", $db->scalarQueries[1]);
		$lock->release('other-name');
		$this->assertCount(2, $db->scalarQueries);
	}

	public function testBusyLockIsNotRecordedForRelease(): void {
		$db = new RecordingDatabaseStub();
		$db->scalarValue = 0;
		$lock = new DatabaseMigrationLock($db);
		$this->assertFalse($lock->acquire('migration'));
		$lock->release('migration');
		$this->assertCount(1, $db->scalarQueries);
	}

	public function testDisconnectedDatabaseFailsBeforeLockQuery(): void {
		$db = new RecordingDatabaseStub();
		$db->connectedFlag = false;
		$lock = new DatabaseMigrationLock($db);
		try {
			$lock->acquire('migration');
			$this->fail('Expected a database connection exception.');
		} catch (MigrationException $error) {
			$this->assertStringContainsString('connection', $error->getMessage());
		}
		$this->assertSame([], $db->scalarQueries);
	}

	public function testDatabaseErrorOnAcquisitionIsReported(): void {
		$db = new RecordingDatabaseStub();
		$db->scalarValue = 1;
		$db->error = true;
		$db->errorText = 'lock service unavailable';
		$this->expectException(MigrationException::class);
		(new DatabaseMigrationLock($db))->acquire('migration');
	}

	public function testReleaseReportsDatabaseErrors(): void {
		$db = new RecordingDatabaseStub();
		$db->scalarValue = 1;
		$lock = new DatabaseMigrationLock($db);
		$this->assertTrue($lock->acquire('migration'));
		$db->error = true;
		$db->errorText = 'connection dropped';
		$this->expectException(MigrationException::class);
		$lock->release('migration');
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
