<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class GroupUserAuthTest extends TestCase {

	public function testWithoutPasswordDoesNotReadCredentialFile(): void {
		$old=$_REQUEST;
		try {
			unset($_REQUEST['password']);
			$a=new \Base3\Accesscontrol\Authentication\GroupUserAuth();
			$this->assertSame('groupuserauth',$a::getName());
			$this->assertNull($a->login());
		} finally { $_REQUEST=$old; }
	}

}
