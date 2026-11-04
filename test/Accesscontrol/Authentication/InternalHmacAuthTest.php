<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class InternalHmacAuthTest extends TestCase {

	public function testAuthenticatedHmacHeadersRequireValidTokenHashAndAge(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$server=$_SERVER;
		try {
			$new=new \Base3\Core\ServiceLocator();\Base3\Core\ServiceLocator::useInstance($new);
			$new->set('classmap',new \stdClass(), \Base3\Api\IContainer::SHARED);
			$new->set('configuration',new \Base3\Configuration\ArrayConfiguration(['microservice'=>['masterpass'=>'topsecret']]), \Base3\Api\IContainer::SHARED);
			$token=new \Base3\Test\Accesscontrol\RecordingAuthToken();
			$new->set('authtoken',$token, \Base3\Api\IContainer::SHARED);
			$a=new \Base3\Accesscontrol\Authentication\InternalHmacAuth();
			$this->assertSame('internalhmacauth',$a::getName());
			$this->assertSame(['depending_services'=>'Ok'],$a->checkDependencies());
			$t=time();
			$_SERVER['HTTP_USER']='internal';$_SERVER['HTTP_TIME']=(string)$t;
			$_SERVER['HTTP_TOKEN']='random-token';
			$_SERVER['HTTP_HASH']=sha1('topsecret'.$t.'random-token');
			$_SERVER['HTTP_AUTH']='trusted';
			$this->assertSame('trusted',$a->login());
			$this->assertCount(1,$token->saves);
			$token->valid=true;
			$this->assertNull($a->login());
			$token->valid=false;
			$_SERVER['HTTP_TIME']=(string)(time()-20000);
			$this->assertNull($a->login());
			$_SERVER['HTTP_TIME']=(string)$t;
			$_SERVER['HTTP_HASH']='invalid';
			$this->assertNull($a->login());
		} finally { $_SERVER=$server;\Base3\Core\ServiceLocator::useInstance($old); }
	}

	public function testIncompleteHeadersCannotAuthenticate(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$server=$_SERVER;
		try {
			$new=new \Base3\Core\ServiceLocator();\Base3\Core\ServiceLocator::useInstance($new);
			$new->set('configuration',new \Base3\Configuration\ArrayConfiguration(['microservice'=>['masterpass'=>'pw']]), \Base3\Api\IContainer::SHARED);
			$a=new \Base3\Accesscontrol\Authentication\InternalHmacAuth();
			unset($_SERVER['HTTP_USER'],$_SERVER['HTTP_TIME'],$_SERVER['HTTP_TOKEN'],$_SERVER['HTTP_HASH']);
			$this->assertNull($a->login());
		} finally { $_SERVER=$server;\Base3\Core\ServiceLocator::useInstance($old); }
	}

}

require_once __DIR__ . '/../RecordingAuthToken.php';
