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

use Base3\Core\Migration\Database\DatabaseMigrationLock;
use Base3\CoreFoundation\Migration\Exception\MigrationException;
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
