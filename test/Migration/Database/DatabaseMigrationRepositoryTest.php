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

use Base3\Core\Migration\Database\DatabaseMigrationRepository;
use Base3\CoreFoundation\Migration\Exception\MigrationException;
use Base3\Test\Support\RecordingDatabaseStub;
use PHPUnit\Framework\TestCase;

final class DatabaseMigrationRepositoryTest extends TestCase {

	public function testTableIsCreatedOnlyOnceAcrossReadsAndWrites(): void {
		$db = new RecordingDatabaseStub();
		$repository = new DatabaseMigrationRepository($db);
		$repository->ensureReady();
		$repository->ensureReady();
		$this->assertSame(2, $db->connectCalls);
		$this->assertCount(1, $db->nonQueries);
		$this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `base3_migrations`', $db->nonQueries[0]);
		$repository->getApplied('project', 'step');
		$repository->markApplied('project', 'step', '2', 'abc', 12);
		$this->assertCount(2, $db->nonQueries);
	}

	public function testAppliedRecordIsReadWithEscapedIdentifiers(): void {
		$db = new RecordingDatabaseStub();
		$db->singleRow = ['provider'=>'project', 'migration'=>'a', 'checksum'=>'abc'];
		$repository = new DatabaseMigrationRepository($db);
		$this->assertSame($db->singleRow, $repository->getApplied("proj'ect", "step'one"));
		$this->assertStringContainsString("proj\\'ect", $db->singleQueries[0]);
		$this->assertStringContainsString("step\\'one", $db->singleQueries[0]);
	}

	public function testMissingAppliedRecordReturnsNull(): void {
		$db = new RecordingDatabaseStub();
		$this->assertNull((new DatabaseMigrationRepository($db))->getApplied('project', 'missing'));
	}

	public function testMarkAppliedWritesVersionChecksumAndDuration(): void {
		$db = new RecordingDatabaseStub();
		(new DatabaseMigrationRepository($db))->markApplied('core', "step'1", '1.2', 'checksum', 27);
		$this->assertCount(2, $db->nonQueries);
		$this->assertStringContainsString("step\\'1", $db->nonQueries[1]);
		$this->assertStringContainsString("'1.2'", $db->nonQueries[1]);
		$this->assertStringContainsString("'checksum'", $db->nonQueries[1]);
		$this->assertStringContainsString('27', $db->nonQueries[1]);
	}

	public function testConnectionFailureDoesNotCreateMigrationTable(): void {
		$db = new RecordingDatabaseStub();
		$db->connectedFlag = false;
		try {
			(new DatabaseMigrationRepository($db))->ensureReady();
			$this->fail('Expected migration connection exception.');
		} catch (MigrationException $error) {
			$this->assertStringContainsString('connection', $error->getMessage());
		}
		$this->assertSame([], $db->nonQueries);
	}

	public function testCreationErrorsArePropagated(): void {
		$db = new RecordingDatabaseStub();
		$db->error = true;
		$db->errorText = 'insufficient grants';
		$this->expectException(MigrationException::class);
		(new DatabaseMigrationRepository($db))->ensureReady();
	}

	public function testReadErrorsArePropagated(): void {
		$db = new RecordingDatabaseStub();
		$repository = new DatabaseMigrationRepository($db);
		$repository->ensureReady();
		$db->error = true;
		$this->expectException(MigrationException::class);
		$repository->getApplied('a', 'b');
	}

	public function testWriteErrorsArePropagated(): void {
		$db = new RecordingDatabaseStub();
		$repository = new DatabaseMigrationRepository($db);
		$repository->ensureReady();
		$db->error = true;
		$this->expectException(MigrationException::class);
		$repository->markApplied('a', 'b', '1', 'digest', 0);
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
