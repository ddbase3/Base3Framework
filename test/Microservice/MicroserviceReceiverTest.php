<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

use PHPUnit\Framework\TestCase;

final class MicroserviceReceiverTest extends TestCase {

	public function testHealthAndRegistrationUseOwnedHelper(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();
		try {
			$container=new \Base3\Core\ServiceLocator();
			\Base3\Core\ServiceLocator::useInstance($container);
			$helper=new ReceiverRegistrationStub();
			$container->set('microservicehelper',$helper,\Base3\Api\IContainer::SHARED);
			$s=new \Base3\Microservice\MicroserviceReceiver();
			$this->assertSame('microservicereceiver',$s::getName());
			$this->assertSame('pong',$s->ping());
			$this->assertSame('Help for MicroserviceReceiver',$s->getHelp());
			$this->assertTrue($s->connect(['service'=>'remote']));
			$this->assertSame(['service'=>'remote'],$helper->registered);
		} finally { \Base3\Core\ServiceLocator::useInstance($old); }
	}

}

require_once __DIR__ . '/ReceiverRegistrationStub.php';
