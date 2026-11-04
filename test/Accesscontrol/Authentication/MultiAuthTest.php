<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class MultiAuthTest extends TestCase {

	public function testReadsConfiguredAccountsAndRejectsWrongPasswords(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$request=$_REQUEST;
		try {
			$new=new \Base3\Core\ServiceLocator();
			\Base3\Core\ServiceLocator::useInstance($new);
			$new->set('configuration',new \Base3\Configuration\ArrayConfiguration(['multiauth'=>['user'=>['first','second'],'pass'=>[sha1('pw1'),sha1('pw2')]]]), \Base3\Api\IContainer::SHARED);
			$a=new \Base3\Accesscontrol\Authentication\MultiAuth();
			$this->assertSame('multiauth',$a::getName());
			$this->assertSame(['depending_services'=>'Ok'],$a->checkDependencies());
			$_REQUEST=['login'=>true,'username'=>'second','password'=>'wrong'];
			$this->assertNull($a->login());
			$_REQUEST['password']='pw2';
			$this->assertSame('second',$a->login());
			unset($_REQUEST['password']);
			$this->assertNull($a->login());
		} finally { $_REQUEST=$request;\Base3\Core\ServiceLocator::useInstance($old); }
	}

}
