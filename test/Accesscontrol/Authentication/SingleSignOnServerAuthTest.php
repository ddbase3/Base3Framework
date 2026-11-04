<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use Base3\Accesscontrol\Authentication\SingleSignOnServerAuth;
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
