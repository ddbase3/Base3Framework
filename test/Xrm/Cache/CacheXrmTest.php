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

namespace Base3\Test\Xrm\Cache;

use Base3\Core\ServiceLocator;
use Base3\Test\Support\RecordingDatabaseStub;
use Base3\Core\Xrm\Cache\CacheXrm;
use PHPUnit\Framework\TestCase;

final class CacheXrmTest extends TestCase {

	private function createXrm(array $user = ['id'=>'alice','role'=>'reader'], array $groups = []): array {
		$original = ServiceLocator::getInstance();
		$container = new ServiceLocator();
		$db = new RecordingDatabaseStub();
		$db->listRows = ['a','b'];
		$container->set('database', $db, ServiceLocator::SHARED);
		$container->set('usermanager', new CacheUsermanagerFixture($user,$groups), ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { $xrm = new CacheXrm('memory'); }
		finally { ServiceLocator::useInstance($original); }
		return [$xrm, $db];
	}

	public function testAdminCanEnumerateEntriesAndFilterByCollection(): void {
		[$xrm,$db] = $this->createXrm(['id'=>'root','role'=>'admin']);
		$this->assertSame('memory', $xrm->getXrmName());
		$this->assertSame(['a','b'], $xrm->getAllEntryIds());
		$this->assertSame(['a','b'], $xrm->getXrmEntryIds("o'neal",true));
		$this->assertSame(2, $db->connectCalls);
		$this->assertSame(['a','b'], $xrm->getAllocIds('xxtyfolder'));
		$this->assertSame([], $xrm->getAllocIds('ordinary'));
	}

	public function testNonAdminCanOnlyQueryEntriesWithAccessJoins(): void {
		[$xrm,$db] = $this->createXrm(['id'=>'alice','role'=>'reader'], [['id'=>'team'],['id'=>"other'group"]]);
		$this->assertSame(['a','b'], $xrm->getAllEntryIds());
		$this->assertSame(['a','b'], $xrm->getXrmEntryIds('other',false));
		$this->assertSame(['a','b'], $xrm->getAllocIds('xxtyfolder'));
		$this->assertSame(3, $db->connectCalls);
	}

	public function testCacheEntryWritesAreExplicitNoOps(): void {
		[$xrm,$db] = $this->createXrm();
		$this->assertTrue($xrm->delEntry('a'));
		$this->assertNull($xrm->setEntry(['id'=>'a']));
		$this->assertNull($xrm->getEntry('a'));
		$this->assertSame([], $xrm->getEntries(['a']));
		$this->assertTrue($xrm->addTag('a','x'));
		$this->assertTrue($xrm->removeTag('a','x'));
		$this->assertTrue($xrm->addApp('a','x'));
		$this->assertTrue($xrm->removeApp('a','x'));
		$this->assertTrue($xrm->addAlloc('a','b'));
		$this->assertTrue($xrm->removeAlloc('a','b'));
		$this->assertSame([], $db->nonQueries);
	}

	public function testDependenciesReflectRegisteredDatabase(): void {
		[$xrm,$db] = $this->createXrm();
		$this->assertSame(['depending_services'=>'Ok'], $xrm->checkDependencies());
	}
}

require_once __DIR__ . '/CacheUsermanagerFixture.php';
require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
