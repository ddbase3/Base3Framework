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

final class CacheNameXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$previous = \Base3\Core\ServiceLocator::getInstance();
		$container = new \Base3\Core\ServiceLocator();
		$db = new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database', $db, \Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try {
			$module = new \Base3\Core\Xrm\Cache\CacheNameXrmFilterModule();
			$xrm = new \Base3\Core\Xrm\Cache\CacheXrm('primary');
		} finally {
			\Base3\Core\ServiceLocator::useInstance($previous);
		}
		return [$module, $db, $xrm];
	}

	public function testMatchAndIdOnlyGuard(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame('cachenamexrmfiltermodule',$module::getName());
		$this->assertSame(2,$module->match($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('name','contains','x')));
		$this->assertSame(0,$module->match(new \stdClass(),new \Base3\CoreFoundation\Xrm\XrmFilter('name','contains','x')));
		$this->assertSame([],$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('name','contains','x')));
		$this->assertSame([],$db->multiQueries);
	}
	public function testNameOperatorsEscapeValuesAndPreserveLimits(): void {
		[$module, $db, $xrm] = $this->createModule();
		$db->multiRows = [['uuid'=>'a']];
		$expected = ['startswith'=>"LIKE 'o\'brien%'", 'endswith'=>"LIKE '%o\'brien'", 'contains'=>"LIKE '%o\'brien%'", 'notcontains'=>"NOT LIKE '%o\'brien%'"];
		foreach ($expected as $operator=>$fragment) {
			$this->assertSame(['a'],$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('name',$operator,"o'brien",2,3),true));
			$this->assertStringContainsString($fragment, $db->multiQueries[count($db->multiQueries)-1]);
			$this->assertStringContainsString('LIMIT 2, 3', $db->multiQueries[count($db->multiQueries)-1]);
		}
	}
	public function testUnsupportedOperatorUsesUnsatisfiableWhereClause(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame([], $module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('type','eq','x'),true));
		$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('name','unknown','x'),true);
		$this->assertStringContainsString('WHERE 0', $db->multiQueries[0]);
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
