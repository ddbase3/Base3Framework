<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class SingleAuthTest extends TestCase {

	public function testNoLoginRequestDoesNotAuthenticate(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$request=$_REQUEST;
		try {
			$new=new \Base3\Core\ServiceLocator();
			\Base3\Core\ServiceLocator::useInstance($new);
			$new->set('configuration', new \Base3\Configuration\ArrayConfiguration(['singleauth'=>['user'=>'member','pass'=>sha1('pw')]]), \Base3\Api\IContainer::SHARED);
			$a=new \Base3\Accesscontrol\Authentication\SingleAuth();
			$this->assertSame('singleauth',$a::getName());
			$this->assertSame(['depending_services'=>'Ok'],$a->checkDependencies());
			unset($_REQUEST['login']);
			$this->assertNull($a->login());
		} finally { $_REQUEST=$request;\Base3\Core\ServiceLocator::useInstance($old); }
	}

}
