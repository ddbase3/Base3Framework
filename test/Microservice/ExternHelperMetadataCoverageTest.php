<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

use Base3\Microservice\Api\IMicroserviceFlags;
use Base3\Microservice\Api\IMicroserviceReceiver;
use Base3\Microservice\Extern\MicroserviceExternConnector;
use Base3\Microservice\Extern\MicroserviceExternHelper;
use PHPUnit\Framework\TestCase;

final class ExternHelperMetadataCoverageTest extends TestCase {

	public function testHelperCreatesConnectorForNamedInterface(): void {
		$helper = new MicroserviceExternHelper();
		$connector = $helper->get('https://host.example.test/service.json', IMicroserviceReceiver::class);
		$this->assertInstanceOf(MicroserviceExternConnector::class, $connector);
		$this->assertSame('https://host.example.test/service.json', $connector->getMicroserviceUrl());
		$this->assertSame($connector, $connector->getProxiedInstance());
	}

	public function testFlagsAreAcceptedWhenBuildingExternalService(): void {
		$helper = new MicroserviceExternHelper(IMicroserviceFlags::SERIALIZED);
		$connector = $helper->get('https://host.example.test/worker.json?token=one', IMicroserviceReceiver::class);
		$this->assertInstanceOf(MicroserviceExternConnector::class, $connector);
		$this->assertSame('https://host.example.test/worker.json?token=one', $connector->getMicroserviceUrl());
	}

	public function testUnknownInterfaceIsRejectedByReflection(): void {
		$helper = new MicroserviceExternHelper();
		$this->expectException(\TypeError::class);
		$helper->get('https://host.example.test/worker.json', 'Base3\\Missing\\InterfaceName');
	}
}
