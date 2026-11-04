<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class CookieAuthTest extends TestCase {

	public function testWithoutCookieAndTokenServiceDeniesLoginAndChecksDependencies(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$cookie=$_COOKIE;$request=$_REQUEST;
		try {
			$new=new \Base3\Core\ServiceLocator();\Base3\Core\ServiceLocator::useInstance($new);
			$a=new \Base3\Accesscontrol\Authentication\CookieAuth();
			$this->assertSame('cookieauth',$a::getName());
			$this->assertSame(['depending_services'=>'Fail'],$a->checkDependencies());
			unset($_COOKIE['authentication'],$_REQUEST['logout']);
			$this->assertNull($a->login());
			$a->keep(null);$a->logout();
			$this->assertNull($a->login());
		} finally { $_COOKIE=$cookie;$_REQUEST=$request;\Base3\Core\ServiceLocator::useInstance($old); }
	}

}
