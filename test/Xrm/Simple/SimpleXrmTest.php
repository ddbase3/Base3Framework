<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Simple;

use Base3\Core\ServiceLocator;
use Base3\Test\Support\RecordingDatabaseStub;
use Base3\Xrm\Simple\SimpleXrm;
use PHPUnit\Framework\TestCase;

final class SimpleXrmTest extends TestCase {

	private function createSimpleXrm(bool $withDatabase=true): array {
		$previous=ServiceLocator::getInstance();
		$container=new ServiceLocator();
		$db=new RecordingDatabaseStub();
		if($withDatabase)$container->set('database',$db,ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { $xrm=new SimpleXrm('catalog'); }
		finally { ServiceLocator::useInstance($previous); }
		return [$xrm,$db];
	}

	public function testDatabaseAvailabilityAndConfiguredName(): void {
		[$xrm]=$this->createSimpleXrm();
		$this->assertSame('catalog',$xrm->getXrmName());
		$this->assertSame(['depending_services'=>'Ok'],$xrm->checkDependencies());
		[$without]=$this->createSimpleXrm(false);
		$this->assertSame(['depending_services'=>'Fail'],$without->checkDependencies());
	}

	public function testPseudoIdsAreNotPersistedAsPhysicalEntries(): void {
		[$xrm,$db]=$this->createSimpleXrm();
		$this->assertFalse($xrm->setEntry((object)['id'=>'xxtaexample']));
		$this->assertSame([], $db->nonQueries);
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
