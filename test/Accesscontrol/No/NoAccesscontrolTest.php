<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\No;

use PHPUnit\Framework\TestCase;

final class NoAccesscontrolTest extends TestCase {

	public function testNoAuthenticationReturnsNoUserIdentity(): void {
		$access = new \Base3\Accesscontrol\No\NoAccesscontrol();
		$access->authenticate();
		$this->assertNull($access->getUserId());
	}

}
