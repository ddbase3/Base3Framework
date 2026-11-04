<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Full;

use PHPUnit\Framework\TestCase;

final class FullAccesscontrolTest extends TestCase {

	public function testExplicitFullAccessIdentityRequiresNoAuthentication(): void {
		$s=new \Base3\Accesscontrol\Full\FullAccesscontrol();
		$this->assertSame('fullaccess',$s->getUserId());
		$s->authenticate();
		$this->assertSame('fullaccess',$s->getUserId());
	}

}
