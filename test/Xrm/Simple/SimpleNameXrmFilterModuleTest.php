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

use PHPUnit\Framework\TestCase;

final class SimpleNameXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$original=\Base3\Core\ServiceLocator::getInstance();
		$container=new \Base3\Core\ServiceLocator();
		$db=new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database',$db,\Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try { $module=new \Base3\Core\Xrm\Simple\SimpleNameXrmFilterModule(); }
		finally { \Base3\Core\ServiceLocator::useInstance($original); }
		return [$module,$db];
	}

	public function testRecognizesFilterNameAndRejectsOtherXrm(): void {
		[$module]=$this->createModule();
		$this->assertSame('simplenamexrmfiltermodule',$module::getName());
		$this->assertSame(0,$module->match(new \stdClass(),new \Base3\CoreFoundation\Xrm\XrmFilter('name','contains','hello')));
	}

	public function testSearchOperationsEscapeSqlAndRespectLimit(): void {
		[$module,$db]=$this->createModule();
		$db->multiRows=[['uuid'=>'a'],['uuid'=>'b']];
		$xrm=new \Base3\Test\Xrm\FilterTestXrm();
		foreach (['startswith'=>"`name` LIKE 'O\\'Reilly%'",'endswith'=>"`name` LIKE '%O\\'Reilly'",'contains'=>"`name` LIKE '%O\\'Reilly%'",'notcontains'=>"`name` NOT LIKE '%O\\'Reilly%'"] as $op=>$expected) {
			$filter=new \Base3\CoreFoundation\Xrm\XrmFilter('name',$op,"O'Reilly",2,3);
			$this->assertSame(['a','b'],$module->getEntries($xrm,$filter,true));
			$this->assertStringContainsString($expected,$db->multiQueries[count($db->multiQueries)-1]);
			$this->assertStringContainsString('LIMIT 2, 3',$db->multiQueries[count($db->multiQueries)-1]);
		}
		$this->assertSame(4,$db->connectCalls);
	}

	public function testMissingAndUnknownNameFilters(): void {
		[$module,$db]=$this->createModule();
		$xrm=new \Base3\Test\Xrm\FilterTestXrm();
		$this->assertSame([],$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('other','eq','x'),true));
		$this->assertSame([],$db->multiQueries);
		$db->multiRows=[['uuid'=>'a']];
		$this->assertSame(['entry:a'],$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('name','unknown','x')));
		$this->assertStringContainsString('WHERE 0',$db->multiQueries[0]);
	}

}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
