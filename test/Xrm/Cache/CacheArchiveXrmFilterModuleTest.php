<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Cache;

use PHPUnit\Framework\TestCase;

final class CacheArchiveXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$previous = \Base3\Core\ServiceLocator::getInstance();
		$container = new \Base3\Core\ServiceLocator();
		$db = new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database', $db, \Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try {
			$module = new \Base3\Xrm\Cache\CacheArchiveXrmFilterModule();
			$xrm = new \Base3\Xrm\Cache\CacheXrm('primary');
		} finally {
			\Base3\Core\ServiceLocator::useInstance($previous);
		}
		return [$module, $db, $xrm];
	}

	public function testMatchOnlyForCacheArchiveFilter(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame('cachearchivexrmfiltermodule', $module::getName());
		$this->assertSame(2, $module->match($xrm, new \Base3\Xrm\XrmFilter('archive','eq',1)));
		$this->assertSame(0, $module->match(new \stdClass(), new \Base3\Xrm\XrmFilter('archive','eq',1)));
		$this->assertSame(0, $module->match($xrm, new \Base3\Xrm\XrmFilter('name','eq',1)));
	}
	public function testArchiveConditionsAndLimitVariants(): void {
		[$module, $db, $xrm] = $this->createModule();
		$db->multiRows = [['uuid'=>'a'],['uuid'=>'b']];
		$this->assertSame(['a','b'], $module->getEntries($xrm,new \Base3\Xrm\XrmFilter('archive','eq',1,4,3),true));
		$this->assertStringContainsString('`archive` = 1 LIMIT 4, 3', $db->multiQueries[0]);
		$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('archive','eq','undef'),true);
		$this->assertStringContainsString('WHERE 0', $db->multiQueries[1]);
		$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('archive','neq','undef',null,8),true);
		$this->assertStringContainsString('WHERE 1 LIMIT 8', $db->multiQueries[2]);
		$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('archive','neq',0,3),true);
		$this->assertStringContainsString('`archive` != 0 LIMIT 3, 18446744073709551615', $db->multiQueries[3]);
	}
	public function testNonIdOnlyAndUnrelatedFiltersDoNotQuery(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame([], $module->getEntries($xrm,new \Base3\Xrm\XrmFilter('archive','eq',1)));
		$this->assertSame([], $module->getEntries($xrm,new \Base3\Xrm\XrmFilter('type','eq',1),true));
		$this->assertSame([], $db->multiQueries);
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
