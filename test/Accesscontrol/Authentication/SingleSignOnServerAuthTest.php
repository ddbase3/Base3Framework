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

namespace Base3\Test\Accesscontrol\Authentication;

use Base3\Core\Accesscontrol\Authentication\SingleSignOnServerAuth;
use Base3\Core\ServiceLocator;
use PHPUnit\Framework\TestCase;

final class SingleSignOnServerAuthTest extends TestCase {

	private function createAuth(bool $withServices=false): SingleSignOnServerAuth {
		$previous=ServiceLocator::getInstance();
		$container=new ServiceLocator();
		if($withServices) {
			$container->set('session',new \stdClass(),ServiceLocator::SHARED);
			$container->set('ssotoken',new \stdClass(),ServiceLocator::SHARED);
		}
		ServiceLocator::useInstance($container);
		try { return new SingleSignOnServerAuth(); }
		finally { ServiceLocator::useInstance($previous); }
	}

	public function testMissingServicesAreReportedAndNoRedirectIsTriggered(): void {
		$before=$_REQUEST;
		try {
			$_REQUEST=[];
			$auth=$this->createAuth();
			$this->assertSame('singlesignonserverauth',$auth::getName());
			$this->assertSame(['depending_services'=>'Fail'],$auth->checkDependencies());
			$this->assertNull($auth->finish(null));
		} finally { $_REQUEST=$before; }
	}

	public function testAvailableServicesPassDependencyCheck(): void {
		$before=$_REQUEST;
		try {
			$_REQUEST=[];
			$auth=$this->createAuth(true);
			$this->assertSame(['depending_services'=>'Ok'],$auth->checkDependencies());
		} finally { $_REQUEST=$before; }
	}
}
