<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Base3;

use PHPUnit\Framework\TestCase;

final class Base3LogXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$previous = \Base3\Core\ServiceLocator::getInstance();
		$container = new \Base3\Core\ServiceLocator();
		$db = new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database', $db, \Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try {
			$module = new \Base3\Xrm\Base3\Base3LogXrmFilterModule();
			$xrm = new \Base3\Xrm\Base3\Base3Xrm('primary');
		} finally {
			\Base3\Core\ServiceLocator::useInstance($previous);
		}
		return [$module, $db, $xrm];
	}

	public function testMatchTargetsOnlyBase3XrmLogFields(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame('base3logxrmfiltermodule',$module::getName());
		$this->assertSame(2,$module->match($xrm,new \Base3\Xrm\XrmFilter('created','eq','x')));
		$this->assertSame(0,$module->match(new \stdClass(),new \Base3\Xrm\XrmFilter('created','eq','x')));
		$this->assertSame(0,$module->match($xrm,new \Base3\Xrm\XrmFilter('type','eq','x')));
	}
	public function testOwnerSelectionAndDateBranchesBuildExpectedJoins(): void {
		[$module, $db, $xrm] = $this->createModule();
		$db->multiRows = [['uuid'=>'a']];
		$this->assertSame(['a'],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('owner','eq',"o'neal"),true));
		$this->assertStringContainsString("u.`name` = 'o\'neal'",$db->multiQueries[0]);
		$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('created','eq','2026-01-01'),true);
		$this->assertStringContainsString("l.`action` = 'new'",$db->multiQueries[1]);
		$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('changed','gt','2026-01-01'),true);
		$this->assertStringContainsString("l.`action` != 'read'",$db->multiQueries[2]);
		$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('changed','le','2026-01-01'),true);
		$this->assertStringContainsString('l2.`id` IS NULL',$db->multiQueries[3]);
	}
	public function testUnrecognizedAttributesDoNotTouchDatabase(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame([],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('type','eq','folder'),true));
		$this->assertSame([],$db->multiQueries);
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
