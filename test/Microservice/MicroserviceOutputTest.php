<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

use Base3\Core\ServiceLocator;
use Base3\Microservice\Microservice;
use PHPUnit\Framework\TestCase;

final class MicroserviceOutputTest extends TestCase {

	public function testConnectRequestUsesRegisteredHelper(): void {
		$before=$_REQUEST;
		$previous=ServiceLocator::getInstance();
		try {
			$container=new ServiceLocator();
			$helper=new RecordingMicroserviceConnectFixture();
			$container->set('microservicehelper',$helper,ServiceLocator::SHARED);
			ServiceLocator::useInstance($container);
			$service=new Microservice();
			$_REQUEST=['call'=>'connect'];
			$this->assertSame('{"result":"done"}',$service->getOutput('json'));
			$this->assertSame(1,$helper->calls);
			$this->assertSame('microservice',$service::getName());
			$this->assertSame('Help on Microservice',$service->getHelp());
		} finally { $_REQUEST=$before;ServiceLocator::useInstance($previous); }
	}
}

require_once __DIR__ . '/RecordingMicroserviceConnectFixture.php';
