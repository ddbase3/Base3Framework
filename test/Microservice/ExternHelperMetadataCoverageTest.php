<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Microservice;

use Base3\CoreFoundation\Microservice\Api\IMicroserviceFlags;
use Base3\CoreFoundation\Microservice\Api\IMicroserviceReceiver;
use Base3\Core\Microservice\Extern\MicroserviceExternConnector;
use Base3\Core\Microservice\Extern\MicroserviceExternHelper;
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
