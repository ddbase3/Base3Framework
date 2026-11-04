<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Base3;

use PHPUnit\Framework\TestCase;

final class Base3TagXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$previous = \Base3\Core\ServiceLocator::getInstance();
		$container = new \Base3\Core\ServiceLocator();
		$db = new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database', $db, \Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try {
			$module = new \Base3\Xrm\Base3\Base3TagXrmFilterModule();
			$xrm = new \Base3\Xrm\Base3\Base3Xrm('primary');
		} finally {
			\Base3\Core\ServiceLocator::useInstance($previous);
		}
		return [$module, $db, $xrm];
	}

	public function testMatchRequiresBase3TagFilter(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame('base3tagxrmfiltermodule',$module::getName());
		$this->assertSame(2,$module->match($xrm,new \Base3\Xrm\XrmFilter('tag','conn','x')));
		$this->assertSame(0,$module->match(new \stdClass(),new \Base3\Xrm\XrmFilter('tag','conn','x')));
	}
	public function testConnectionAndNegationUseDistinctJoinsAndEscapedTags(): void {
		[$module, $db, $xrm] = $this->createModule();
		$db->multiRows = [['uuid'=>'a']];
		$this->assertSame(['a'],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('tag','conn',"x'y",3,5),true));
		$this->assertStringContainsString('INNER JOIN `base3system_systag`',$db->multiQueries[0]);
		$this->assertStringContainsString("x\'y",$db->multiQueries[0]);
		$this->assertStringContainsString('LIMIT 3, 5',$db->multiQueries[0]);
		$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('tag','notconn','x'),true);
		$this->assertStringContainsString('LEFT JOIN `base3system_systag`',$db->multiQueries[1]);
		$this->assertStringContainsString('IS NULL',$db->multiQueries[1]);
	}
	public function testUnsupportedTagOperatorDoesNotQuery(): void {
		[$module, $db, $xrm] = $this->createModule();
		$this->assertSame([],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('tag','eq','x'),true));
		$this->assertSame([],$module->getEntries($xrm,new \Base3\Xrm\XrmFilter('name','conn','x'),true));
		$this->assertSame([],$db->multiQueries);
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
