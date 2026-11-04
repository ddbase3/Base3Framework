<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class SingleSignOnAutoAuthTest extends TestCase {

	public function testAuthenticatedAndPostRequestsDoNotAttemptRedirect(): void {
		$old=$_SERVER;
		try {
			$c=new \Base3\Configuration\ArrayConfiguration();
			$container=new \Base3\Test\Core\ContainerStub();
			$session=new \Base3\Session\NoSession\NoSession();
			$a=new \Base3\Accesscontrol\Authentication\SingleSignOnAutoAuth($c,$session,$container);
			$this->assertSame('singlesignonautoauth',$a::getName());
			$this->assertSame(['depending_services'=>'Fail'],$a->checkDependencies());
			$a->finish('member');
			$_SERVER['REQUEST_METHOD']='POST';
			$a->finish(null);
			unset($_SERVER['REQUEST_METHOD']);
			$_SERVER['HTTP_X_REQUESTED_WITH']='XMLHttpRequest';
			$a->finish(null);
			$this->assertNull($a->login());
		} finally { $_SERVER=$old; }
	}

}

require_once __DIR__ . '/../../Core/ContainerStub.php';
