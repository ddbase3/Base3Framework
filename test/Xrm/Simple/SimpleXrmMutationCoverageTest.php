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

namespace Base3\Test\Xrm\Simple;

use Base3\Core\ServiceLocator;
use Base3\Test\Support\RecordingDatabaseStub;
use Base3\Test\Xrm\Cache\CacheUsermanagerFixture;
use Base3\Test\Xrm\XrmSearchFixture;
use Base3\Core\Xrm\Simple\SimpleXrm;
use Base3\CoreFoundation\Xrm\XrmEntry;
use PHPUnit\Framework\TestCase;

final class SimpleXrmMutationCoverageTest extends TestCase {

	private function createXrm(bool $controlled = true, ?RecordingDatabaseStub $db = null, ?XrmSearchFixture $global = null): array {
		$previous = ServiceLocator::getInstance();
		$container = new ServiceLocator();
		$db ??= new RecordingDatabaseStub();
		$global ??= new XrmSearchFixture();
		$container->set('database', $db, ServiceLocator::SHARED);
		$container->set('xrmglobal', $global, ServiceLocator::SHARED);
		$container->set('usermanager', new CacheUsermanagerFixture(['id'=>'admin','role'=>'admin']), ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { $xrm = $controlled ? new SimpleXrmMutationFixture('catalog') : new SimpleXrm('catalog'); }
		finally { ServiceLocator::useInstance($previous); }
		return [$xrm,$db,$global];
	}

	public function testDeleteRejectsUnknownEntriesBeforeDatabaseAccess(): void {
		[$xrm,$db] = $this->createXrm();
		$this->assertFalse($xrm->delEntry('missing'));
		$this->assertSame(['missing'], $xrm->lookups);
		$this->assertSame(0, $db->connectCalls);
		$this->assertSame([], $db->nonQueries);
	}

	public function testDeleteRequiresWriteAccess(): void {
		[$xrm,$db] = $this->createXrm();
		$xrm->currentEntry = new XrmEntry();
		$xrm->currentAccess = 'read';
		$this->assertFalse($xrm->delEntry('protected'));
		$this->assertSame([], $db->singleQueries);
		$this->assertSame([], $db->nonQueries);
	}

	public function testDeleteReturnsFalseIfUnderlyingSystemEntryIsMissing(): void {
		[$xrm,$db] = $this->createXrm();
		$xrm->currentEntry = new XrmEntry();
		$xrm->currentAccess = 'write';
		$this->assertFalse($xrm->delEntry('abc'));
		$this->assertSame(1, $db->connectCalls);
		$this->assertCount(1, $db->singleQueries);
		$this->assertSame([], $db->nonQueries);
	}

	public function testFullDeleteRemovesEntryAndLinkedRecords(): void {
		$db = new RecordingDatabaseStub();
		$db->singleRow = ['type'=>'note'];
		[$xrm] = $this->createXrm(true,$db);
		$xrm->currentEntry = new XrmEntry();
		$xrm->currentAccess = 'write';
		$this->assertTrue($xrm->delEntry('abcd', false));
		$this->assertCount(7, $db->nonQueries);
		$this->assertStringContainsString('DELETE FROM `data_note`', $db->nonQueries[0]);
		$this->assertStringContainsString('DELETE FROM `alloc`', $db->nonQueries[1]);
		$this->assertStringContainsString('DELETE FROM `entry`', $db->nonQueries[6]);
	}

	public function testMoveOnlyDeletePreservesAllocationRows(): void {
		$db = new RecordingDatabaseStub();
		$db->singleRow = ['type'=>'note'];
		[$xrm] = $this->createXrm(true,$db);
		$xrm->currentEntry = new XrmEntry();
		$xrm->currentAccess = 'write';
		$this->assertTrue($xrm->delEntry('abcd', true));
		$this->assertCount(6, $db->nonQueries);
		foreach ($db->nonQueries as $sql) $this->assertFalse(str_contains($sql,'DELETE FROM `alloc`'));
	}

	public function testTagAndAppChangesRejectNonWritableEntries(): void {
		[$xrm,$db] = $this->createXrm();
		$xrm->currentEntry = new XrmEntry();
		$xrm->currentAccess = 'read';
		$this->assertFalse($xrm->addTag('a','tag'));
		$this->assertFalse($xrm->removeTag('a','tag'));
		$this->assertFalse($xrm->addApp('a','app'));
		$this->assertFalse($xrm->removeApp('a','app'));
		$this->assertSame([], $db->nonQueries);
	}

	public function testTagMutationsBuildEscapedSqlAtDatabaseBoundary(): void {
		[$xrm,$db] = $this->createXrm();
		$xrm->currentEntry = new XrmEntry();
		$xrm->currentAccess = 'write';
		$this->assertTrue($xrm->addTag('abcd', "it's"));
		$this->assertTrue($xrm->removeTag('abcd', "it's"));
		$this->assertCount(2, $db->nonQueries);
		$this->assertStringContainsString("it\\'s", $db->nonQueries[0]);
		$this->assertStringContainsString('INSERT INTO `tag`', $db->nonQueries[0]);
		$this->assertStringContainsString('DELETE FROM `tag`', $db->nonQueries[1]);
	}

	public function testAppMutationsWriteOnlyWhenAuthorized(): void {
		[$xrm,$db] = $this->createXrm();
		$xrm->currentEntry = new XrmEntry();
		$xrm->currentAccess = 'write';
		$this->assertTrue($xrm->addApp('abcd','cms'));
		$this->assertTrue($xrm->removeApp('abcd','cms'));
		$this->assertCount(2, $db->nonQueries);
		$this->assertStringContainsString('INSERT INTO `app`', $db->nonQueries[0]);
		$this->assertStringContainsString('DELETE FROM `app`', $db->nonQueries[1]);
	}

	public function testAllocationMutationsDenyMissingOrUnreadableSource(): void {
		[$xrm,$db] = $this->createXrm();
		$this->assertFalse($xrm->addAlloc('a','b'));
		$this->assertFalse($xrm->removeAlloc('a','b'));
		$xrm->currentEntry = new XrmEntry();
		$xrm->currentAccess = 'read';
		$this->assertFalse($xrm->addAlloc('a','b'));
		$this->assertFalse($xrm->removeAlloc('a','b'));
		$this->assertSame([], $db->nonQueries);
	}

	public function testAllocationInsertCanonicalizesDirection(): void {
		[$xrm,$db] = $this->createXrm();
		$xrm->currentEntry = new XrmEntry();
		$xrm->currentAccess = 'write';
		$this->assertTrue($xrm->addAlloc('ff','aa'));
		$this->assertStringContainsString('`id1` = 0xaa, `id2` = 0xff', $db->nonQueries[0]);
		$this->assertTrue($xrm->addAlloc('aa','ff'));
		$this->assertStringContainsString('`id1` = 0xaa, `id2` = 0xff', $db->nonQueries[1]);
	}

	public function testAllocationDeleteMatchesEitherDirection(): void {
		[$xrm,$db] = $this->createXrm();
		$xrm->currentEntry = new XrmEntry();
		$xrm->currentAccess = 'write';
		$this->assertTrue($xrm->removeAlloc('aa','bb'));
		$this->assertStringContainsString('DELETE FROM `alloc`', $db->nonQueries[0]);
		$this->assertStringContainsString('`id1` = 0xaa AND `id2` = 0xbb', $db->nonQueries[0]);
		$this->assertStringContainsString('`id2` = 0xaa AND `id1` = 0xbb', $db->nonQueries[0]);
	}

	public function testAllEntryIdsForAdminUseUnrestrictedEntrySelection(): void {
		$db = new RecordingDatabaseStub();
		$db->multiRows = [['uuid'=>'a'],['uuid'=>'b']];
		[$xrm] = $this->createXrm(false,$db);
		$this->assertSame(['a','b'], $xrm->getAllEntryIds());
		$this->assertStringContainsString('FROM `entry`', $db->multiQueries[0]);
		$this->assertFalse(str_contains($db->multiQueries[0], 'LEFT JOIN `access`'));
	}

	public function testXrmIdQueriesRejectUnrelatedCollectionWithoutDatabase(): void {
		[$xrm,$db] = $this->createXrm(false);
		$this->assertSame([], $xrm->getXrmEntryIds('other'));
		$this->assertSame([], $xrm->getXrmEntryIds('catalog', true));
		$this->assertSame([], $db->multiQueries);
	}

	public function testXrmIdQueriesAllowOwnCollectionAndInvertedOtherCollection(): void {
		$db = new RecordingDatabaseStub();
		$db->multiRows = [['uuid'=>'a']];
		[$xrm] = $this->createXrm(false,$db);
		$this->assertSame(['a'], $xrm->getXrmEntryIds('catalog'));
		$this->assertSame(['a'], $xrm->getXrmEntryIds('other', true));
		$this->assertCount(2, $db->multiQueries);
	}

	public function testAllocIdsTagQueryPassesCandidateIdsToGlobalFilter(): void {
		$db = new RecordingDatabaseStub();
		$db->multiRows = [['uuid'=>'one']];
		$global = new XrmSearchFixture();
		[$xrm] = $this->createXrm(false,$db,$global);
		$this->assertSame(['one','two'], $xrm->getAllocIds('xxtaproject'));
		$this->assertStringContainsString('FROM `tag`', $db->multiQueries[0]);
		$this->assertSame('ids', $global->calls[0][1]->attr);
		$this->assertSame(['one'], $global->calls[0][1]->val);
		$this->assertTrue($global->calls[0][2]);
	}

	public function testAllocIdsDistinguishesAppsTypesAndRegularEntries(): void {
		$db = new RecordingDatabaseStub();
		$db->multiRows = [['uuid'=>'a']];
		[$xrm] = $this->createXrm(false,$db);
		$xrm->getAllocIds('xxapcms');
		$xrm->getAllocIds('xxtynote');
		$xrm->getAllocIds('abcd');
		$this->assertStringContainsString('FROM `app`', $db->multiQueries[0]);
		$this->assertStringContainsString('FROM `entry`', $db->multiQueries[1]);
		$this->assertStringContainsString('FROM `alloc`', $db->multiQueries[2]);
	}

	public function testEmptyReadListReturnsWithoutDatabaseQuery(): void {
		[$xrm,$db] = $this->createXrm(false);
		$this->assertSame([], $xrm->getEntries([]));
		$this->assertSame([], $db->multiQueries);
	}

	public function testGetEntryWithNoDatabaseRowReturnsNull(): void {
		[$xrm,$db] = $this->createXrm(false);
		$this->assertNull($xrm->getEntry('aaaa'));
		$this->assertCount(1, $db->multiQueries);
	}

	public function testPseudoEntryIdsAreRejectedBeforeWrites(): void {
		[$xrm,$db] = $this->createXrm();
		$this->assertFalse($xrm->setEntry((object)['id'=>'xxapfake']));
		$this->assertSame([], $db->nonQueries);
	}

	public function testUpdatingExistingEntryRequiresWritePermission(): void {
		[$xrm,$db] = $this->createXrm();
		$xrm->currentEntry = new XrmEntry();
		$xrm->currentAccess = 'read';
		$this->assertNull($xrm->setEntry((object)['id'=>'abcd','name'=>'Protected','alloc'=>[]]));
		$this->assertSame([], $db->nonQueries);
	}

	public function testNewEntryCreatesOwnerAccessAndAuditLog(): void {
		[$xrm,$db] = $this->createXrm();
		$entry = new XrmEntry();
		$entry->name = 'New';
		$entry->type = 'note';
		$entry->data = ['text'=>'hello'];
		$created = $xrm->setEntry($entry);
		$this->assertInstanceOf(XrmEntry::class, $created);
		$this->assertSame(32, strlen($created->id));
		$this->assertSame('created', $created->log[0]->action);
		$this->assertSame('admin', $created->access[0]->id);
		$this->assertSame('owner', $created->access[0]->mode);
		$this->assertTrue(in_array('catalog', $created->xrmnames, true));
		$this->assertTrue(count($db->nonQueries) >= 4);
		$this->assertStringContainsString('INSERT INTO `entry`', $db->nonQueries[0]);
	}
}

require_once __DIR__ . '/SimpleXrmMutationFixture.php';
require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../Cache/CacheUsermanagerFixture.php';
require_once __DIR__ . '/../XrmSearchFixture.php';
