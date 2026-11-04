<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use Base3\Accesscontrol\Authentication\CliAuth;
use PHPUnit\Framework\TestCase;

final class AbstractAuthDefaultsCoverageTest extends TestCase {

	public function testBaseAuthLifecycleMethodsAreHarmlessNoOps(): void {
		$auth = new CliAuth();
		$this->assertNull($auth->keep('alice'));
		$this->assertNull($auth->finish('alice'));
		$this->assertNull($auth->logout());
	}

	public function testVerboseSetterDoesNotChangeCliAuthentication(): void {
		$auth = new CliAuth();
		$auth->setVerbose(true);
		$this->assertSame('internal', $auth->login());
		$auth->setVerbose(false);
		$this->assertSame('internal', $auth->login());
		$this->assertSame('cliauth', $auth::getName());
	}
}
