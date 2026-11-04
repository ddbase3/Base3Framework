<?php declare(strict_types=1);

namespace Base3\Test\Token;

use PHPUnit\Framework\TestCase;

final class TokenProxyTest extends TestCase {

	public function testDelegatesAllTokenOperationsToProvidedConnector(): void {
		$connector = new \Base3\Test\Token\TokenProxyTestConnector();
		$token = new \Base3\Token\TokenProxy($connector);
		$this->assertSame('token', $token->create('login', 5, 16, 600));
		$this->assertTrue($token->check('login', 5, 'token'));
		$this->assertFalse($token->check('login', 5, 'wrong'));
		$token->delete('login', 5, 'token');
		$token->clean('login', 5);
		$this->assertSame([
			['create','login',5,16,600], ['check','login',5,'token'], ['check','login',5,'wrong'],
			['delete','login',5,'token'], ['clean','login',5]
		], $connector->calls);
	}

}

require_once __DIR__ . '/TokenProxyTestConnector.php';
