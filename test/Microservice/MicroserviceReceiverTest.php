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

use PHPUnit\Framework\TestCase;

final class MicroserviceReceiverTest extends TestCase {

	public function testHealthAndRegistrationUseOwnedHelper(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();
		try {
			$container=new \Base3\Core\ServiceLocator();
			\Base3\Core\ServiceLocator::useInstance($container);
			$helper=new ReceiverRegistrationStub();
			$container->set('microservicehelper',$helper,\Base3\CoreFoundation\Api\IContainer::SHARED);
			$s=new \Base3\Core\Microservice\MicroserviceReceiver();
			$this->assertSame('microservicereceiver',$s::getName());
			$this->assertSame('pong',$s->ping());
			$this->assertSame('Help for MicroserviceReceiver',$s->getHelp());
			$this->assertTrue($s->connect(['service'=>'remote']));
			$this->assertSame(['service'=>'remote'],$helper->registered);
		} finally { \Base3\Core\ServiceLocator::useInstance($old); }
	}

}

require_once __DIR__ . '/ReceiverRegistrationStub.php';
