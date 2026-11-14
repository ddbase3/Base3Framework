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

use Base3\Core\ServiceLocator;
use Base3\Core\Microservice\Microservice;
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
