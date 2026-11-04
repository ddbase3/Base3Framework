<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class SessionAuthTest extends TestCase {

	public function testSessionAuthenticationLifecycleWithoutSessionStartup(): void {
		$session=$_SESSION ?? [];
		try {
			$_SESSION=[];
			$a=new \Base3\Accesscontrol\Authentication\SessionAuth(new \Base3\Session\NoSession\NoSession());
			$this->assertSame('sessionauth',$a::getName());
			$this->assertSame(['session_started'=>'Fail'],$a->checkDependencies());
			$this->assertNull($a->login());
			$a->keep('member');
			$this->assertSame('member',$a->login());
			$this->assertSame('member',$_SESSION['authentication']['userid']);
			$a->keep(null);
			$this->assertSame('member',$_SESSION['authentication']['userid']);
		} finally { $_SESSION=$session; }
	}

}
