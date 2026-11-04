<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Simple;

use PHPUnit\Framework\TestCase;

final class SimpleLogXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$original=\Base3\Core\ServiceLocator::getInstance();
		$container=new \Base3\Core\ServiceLocator();
		$db=new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database',$db,\Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try { $module=new \Base3\Xrm\Simple\SimpleLogXrmFilterModule(); }
		finally { \Base3\Core\ServiceLocator::useInstance($original); }
		return [$module,$db];
	}

	public function testOwnerAndCreatedQueriesUseDifferentClauses(): void {
		[$module,$db]=$this->createModule();
		$db->multiRows=[['uuid'=>'a']];
		$xrm=new \Base3\Test\Xrm\FilterTestXrm();
		$this->assertSame(['a'],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('owner','eq','alice',null,7),true));
		$this->assertStringContainsString("`action` = 'created' AND `user` = 'alice' LIMIT 7",$db->multiQueries[0]);
		$this->assertSame(['entry:a'],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('created','gt','2026-01-01')));
		$this->assertStringContainsString("`timestamp` > '2026-01-01 23:59:59%'",$db->multiQueries[1]);
	}

	public function testChangedQueriesSelectCurrentAndLatestRows(): void {
		[$module,$db]=$this->createModule();
		$xrm=new \Base3\Test\Xrm\FilterTestXrm();
		$db->multiRows=[['uuid'=>'a']];
		$this->assertSame(['a'],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('changed','ge','2026-01-01'),true));
		$this->assertStringContainsString("`action` != 'read'",$db->multiQueries[0]);
		$this->assertSame(['a'],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('changed','le','2026-01-01'),true));
		$this->assertStringContainsString('LEFT JOIN `log` l2',$db->multiQueries[1]);
	}

	public function testInvalidAttributesDoNotQuery(): void {
		[$module,$db]=$this->createModule();
		$this->assertSame('simplelogxrmfiltermodule',$module::getName());
		$this->assertSame(0,$module->match(new \stdClass(),new \Base3\Xrm\XrmFilter('owner','eq','a')));
		$this->assertSame([],$module->getEntries(new \Base3\Test\Xrm\FilterTestXrm(),new \Base3\Xrm\XrmFilter('other','eq','a'),true));
		$this->assertSame([],$db->multiQueries);
	}

}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
