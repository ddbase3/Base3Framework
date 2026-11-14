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

use PHPUnit\Framework\TestCase;

final class CacheLogXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$previous = \Base3\Core\ServiceLocator::getInstance();
		$container = new \Base3\Core\ServiceLocator();
		$db = new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database', $db, \Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try {
			$module = new \Base3\Core\Xrm\Cache\CacheLogXrmFilterModule();
			$xrm = new \Base3\Core\Xrm\Cache\CacheXrm('primary');
		} finally {
			\Base3\Core\ServiceLocator::useInstance($previous);
		}
		return [$module, $db, $xrm];
	}

	public function testMatchAcceptsOnlyCacheDateAndOwnerAttributes(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame('cachelogxrmfiltermodule', $module::getName());
		foreach (['owner','created','changed'] as $attr) {
			$this->assertSame(2, $module->match($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter($attr,'eq','x')));
		}
		$this->assertSame(0, $module->match(new \stdClass(),new \Base3\CoreFoundation\Xrm\XrmFilter('owner','eq','x')));
		$this->assertSame(0, $module->match($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('name','eq','x')));
	}
	public function testOwnerEqualityAndInequalityUseEscapedValues(): void {
		[$module, $db, $xrm] = $this->createModule();
		$db->multiRows = [['uuid'=>'a']];
		$this->assertSame(['a'], $module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('owner','eq',"o'neal",null,5),true));
		$this->assertStringContainsString("`owner`  =  'o\'neal' LIMIT 5", $db->multiQueries[0]);
		$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('owner','ne','alice'),true);
		$this->assertStringContainsString("`owner`  !=  'alice'", $db->multiQueries[1]);
	}
	public function testDateComparisonOperationsSelectExpectedSql(): void {
		[$module, $db, $xrm] = $this->createModule();
		foreach (['eq'=>' LIKE ', 'ne'=>' NOT LIKE ', 'gt'=>' > ', 'ge'=>' > ', 'lt'=>' < ', 'le'=>' <= '] as $op=>$sql) {
			$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('changed',$op,'2026-11-01'),true);
			$this->assertStringContainsString($sql,$db->multiQueries[count($db->multiQueries)-1]);
		}
	}
	public function testNonIdOnlyAndUnknownFieldsStayEmpty(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame([], $module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('owner','eq','x')));
		$this->assertSame([], $module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('other','eq','x'),true));
		$this->assertSame([], $db->multiQueries);
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
