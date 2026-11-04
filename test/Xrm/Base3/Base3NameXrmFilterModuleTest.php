<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Base3;

use PHPUnit\Framework\TestCase;

final class Base3NameXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$previous = \Base3\Core\ServiceLocator::getInstance();
		$container = new \Base3\Core\ServiceLocator();
		$db = new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database', $db, \Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try {
			$module = new \Base3\Xrm\Base3\Base3NameXrmFilterModule();
			$xrm = new \Base3\Xrm\Base3\Base3Xrm('primary');
		} finally {
			\Base3\Core\ServiceLocator::useInstance($previous);
		}
		return [$module, $db, $xrm];
	}

	public function testMatchRequiresBase3XrmNameFilter(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame('base3namexrmfiltermodule',$module::getName());
		$this->assertSame(2,$module->match($xrm,new \Base3\Xrm\XrmFilter('name','contains','a')));
		$this->assertSame(0,$module->match(new \stdClass(),new \Base3\Xrm\XrmFilter('name','contains','a')));
	}
	public function testNameOperationsPreserveEscapingAndIdOnlyResults(): void {
		[$module, $db, $xrm] = $this->createModule();
		$db->multiRows = [['uuid'=>'a']];
		foreach (['startswith'=>"LIKE 'o\'brien%'",'endswith'=>"LIKE '%o\'brien'",'contains'=>"LIKE '%o\'brien%'",'notcontains'=>"NOT LIKE '%o\'brien%'"] as $operator=>$sql) {
			$this->assertSame(['a'],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('name',$operator,"o'brien",null,4),true));
			$this->assertStringContainsString($sql,$db->multiQueries[count($db->multiQueries)-1]);
		}
	}
	public function testUnknownFieldDoesNotQuery(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame([],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('other','eq','x'),true));
		$this->assertSame([],$db->multiQueries);
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
