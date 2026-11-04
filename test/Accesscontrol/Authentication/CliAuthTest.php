<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class CliAuthTest extends TestCase {

	public function testCliIdentityAndDefaultLifecycle(): void {
		$a=new \Base3\Accesscontrol\Authentication\CliAuth();
		$this->assertSame('cliauth',$a::getName());
		$this->assertSame(php_sapi_name()==='cli' ? 'internal' : null,$a->login());
		$a->setVerbose(false);
		$a->logout();$a->keep(null);$a->finish(null);
		$this->assertSame('cliauth',$a::getName());
	}

}
