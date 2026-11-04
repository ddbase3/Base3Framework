<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Simple;

use PHPUnit\Framework\TestCase;

final class SimpleArchiveXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$original=\Base3\Core\ServiceLocator::getInstance();
		$container=new \Base3\Core\ServiceLocator();
		$db=new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database',$db,\Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try { $module=new \Base3\Xrm\Simple\SimpleArchiveXrmFilterModule(); }
		finally { \Base3\Core\ServiceLocator::useInstance($original); }
		return [$module,$db];
	}

	public function testArchiveQueriesSupportEqualityNegationAndLimit(): void {
		[$module,$db]=$this->createModule();
		$db->multiRows=[['uuid'=>'a']];
		$xrm=new \Base3\Test\Xrm\FilterTestXrm();
		$this->assertSame(['a'],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('archive','eq',1,2,1),true));
		$this->assertStringContainsString('`archive` = 1 LIMIT 2, 1',$db->multiQueries[0]);
		$this->assertSame(['entry:a'],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('archive','ne',0)));
		$this->assertStringContainsString('`archive` != 0',$db->multiQueries[1]);
	}

	public function testInvalidArchiveValuesDoNotQuery(): void {
		[$module,$db]=$this->createModule();
		$this->assertSame('simplearchivexrmfiltermodule',$module::getName());
		$this->assertSame(0,$module->match(new \stdClass(),new \Base3\Xrm\XrmFilter('archive','eq',0)));
		$xrm=new \Base3\Test\Xrm\FilterTestXrm();
		$this->assertSame([],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('archive','eq','invalid'),true));
		$this->assertSame([],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('other','eq',0),true));
		$this->assertSame([],$db->multiQueries);
	}

}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
