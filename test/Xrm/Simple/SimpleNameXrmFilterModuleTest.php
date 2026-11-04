<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Simple;

use PHPUnit\Framework\TestCase;

final class SimpleNameXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$original=\Base3\Core\ServiceLocator::getInstance();
		$container=new \Base3\Core\ServiceLocator();
		$db=new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database',$db,\Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try { $module=new \Base3\Xrm\Simple\SimpleNameXrmFilterModule(); }
		finally { \Base3\Core\ServiceLocator::useInstance($original); }
		return [$module,$db];
	}

	public function testRecognizesFilterNameAndRejectsOtherXrm(): void {
		[$module]=$this->createModule();
		$this->assertSame('simplenamexrmfiltermodule',$module::getName());
		$this->assertSame(0,$module->match(new \stdClass(),new \Base3\Xrm\XrmFilter('name','contains','hello')));
	}

	public function testSearchOperationsEscapeSqlAndRespectLimit(): void {
		[$module,$db]=$this->createModule();
		$db->multiRows=[['uuid'=>'a'],['uuid'=>'b']];
		$xrm=new \Base3\Test\Xrm\FilterTestXrm();
		foreach (['startswith'=>"`name` LIKE 'O\\'Reilly%'",'endswith'=>"`name` LIKE '%O\\'Reilly'",'contains'=>"`name` LIKE '%O\\'Reilly%'",'notcontains'=>"`name` NOT LIKE '%O\\'Reilly%'"] as $op=>$expected) {
			$filter=new \Base3\Xrm\XrmFilter('name',$op,"O'Reilly",2,3);
			$this->assertSame(['a','b'],$module->getEntries($xrm,$filter,true));
			$this->assertStringContainsString($expected,$db->multiQueries[count($db->multiQueries)-1]);
			$this->assertStringContainsString('LIMIT 2, 3',$db->multiQueries[count($db->multiQueries)-1]);
		}
		$this->assertSame(4,$db->connectCalls);
	}

	public function testMissingAndUnknownNameFilters(): void {
		[$module,$db]=$this->createModule();
		$xrm=new \Base3\Test\Xrm\FilterTestXrm();
		$this->assertSame([],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('other','eq','x'),true));
		$this->assertSame([],$db->multiQueries);
		$db->multiRows=[['uuid'=>'a']];
		$this->assertSame(['entry:a'],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('name','unknown','x')));
		$this->assertStringContainsString('WHERE 0',$db->multiQueries[0]);
	}

}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
