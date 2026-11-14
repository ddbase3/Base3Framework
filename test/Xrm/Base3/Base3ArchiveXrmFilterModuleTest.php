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

namespace Base3\Test\Xrm\Base3;

use PHPUnit\Framework\TestCase;

final class Base3ArchiveXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$previous = \Base3\Core\ServiceLocator::getInstance();
		$container = new \Base3\Core\ServiceLocator();
		$db = new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database', $db, \Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try {
			$module = new \Base3\Core\Xrm\Base3\Base3ArchiveXrmFilterModule();
			$xrm = new \Base3\Core\Xrm\Base3\Base3Xrm('primary');
		} finally {
			\Base3\Core\ServiceLocator::useInstance($previous);
		}
		return [$module, $db, $xrm];
	}

	public function testMatchRequiresBase3XrmAndArchiveAttribute(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame('base3archivexrmfiltermodule',$module::getName());
		$this->assertSame(2,$module->match($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','eq',1)));
		$this->assertSame(0,$module->match(new \stdClass(),new \Base3\CoreFoundation\Xrm\XrmFilter('archive','eq',1)));
	}
	public function testArchiveValuesGenerateLimitedSqlAndIds(): void {
		[$module, $db, $xrm] = $this->createModule();
		$db->multiRows = [['uuid'=>'a']];
		$this->assertSame(['a'],$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','eq',1,3,5),true));
		$this->assertStringContainsString('`archive` = 1 LIMIT 3, 5',$db->multiQueries[0]);
		$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','ne',0),true);
		$this->assertStringContainsString('`archive` != 0',$db->multiQueries[1]);
	}
	public function testInvalidArchiveValueDoesNotQuery(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame([],$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('archive','eq',9),true));
		$this->assertSame([],$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('other','eq',1),true));
		$this->assertSame([],$db->multiQueries);
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
