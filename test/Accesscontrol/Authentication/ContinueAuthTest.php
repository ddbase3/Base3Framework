<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class ContinueAuthTest extends TestCase {

	public function testNoContinueRequestReturnsWithoutRedirect(): void {
		$old=$_REQUEST;
		try {
			unset($_REQUEST['_continueauth']);
			$a=new \Base3\Accesscontrol\Authentication\ContinueAuth();
			$this->assertSame('continueauth',$a::getName());
			$a->finish(null);
			$this->assertNull($a->login());
		} finally { $_REQUEST=$old; }
	}

}
