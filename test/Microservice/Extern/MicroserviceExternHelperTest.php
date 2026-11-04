<?php declare(strict_types=1);

namespace Base3\Test\Microservice\Extern;

use Base3\Cache\Api\ICache;
use Base3\Microservice\Extern\MicroserviceExternConnector;
use Base3\Microservice\Extern\MicroserviceExternHelper;
use PHPUnit\Framework\TestCase;

final class MicroserviceExternHelperTest extends TestCase {

	public function testDescriptorReflectsInterfaceWithoutMakingNetworkCalls(): void {
		$helper = new MicroserviceExternHelper();
		$connector = $helper->get('https://example.invalid/cache.json',ICache::class);
		$this->assertInstanceOf(MicroserviceExternConnector::class,$connector);
		$this->assertSame('https://example.invalid/cache.json',$connector->getMicroserviceUrl());
		$this->assertSame($connector,$connector->getProxiedInstance());
	}

	public function testHelperAcceptsConfiguredFlagsWithoutInvokingProxyMethods(): void {
		$helper = new MicroserviceExternHelper(1);
		$connector = $helper->get('https://example.invalid/status.json',\Base3\Api\ICheck::class);
		$this->assertInstanceOf(MicroserviceExternConnector::class,$connector);
		$this->assertSame('https://example.invalid/status.json',$connector->getMicroserviceUrl());
	}
}
