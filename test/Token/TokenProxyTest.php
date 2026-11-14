<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Token;

use PHPUnit\Framework\TestCase;

final class TokenProxyTest extends TestCase {

	public function testDelegatesAllTokenOperationsToProvidedConnector(): void {
		$connector = new \Base3\Test\Token\TokenProxyTestConnector();
		$token = new \Base3\Core\Token\TokenProxy($connector);
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
