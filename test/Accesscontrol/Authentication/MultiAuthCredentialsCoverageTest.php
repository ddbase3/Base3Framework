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

namespace Base3\Test\Accesscontrol\Authentication;

use Base3\Core\Accesscontrol\Authentication\MultiAuth;
use Base3\CoreFoundation\Api\IContainer;
use Base3\Core\Configuration\ArrayConfiguration;
use Base3\Core\ServiceLocator;
use PHPUnit\Framework\TestCase;

final class MultiAuthCredentialsCoverageTest extends TestCase {

	private function auth(?array $users): MultiAuth {
		$container = new ServiceLocator();
		if ($users !== null) {
			$container->set('configuration', new ArrayConfiguration(['multiauth' => $users]), IContainer::SHARED);
		}
		ServiceLocator::useInstance($container);
		return new MultiAuth();
	}

	private function withRequest(callable $assertions): void {
		$originalRequest = $_REQUEST;
		$previousLocator = ServiceLocator::getInstance();
		try { $_REQUEST = []; $assertions(); }
		finally { $_REQUEST = $originalRequest; ServiceLocator::useInstance($previousLocator); }
	}

	public function testMissingConfigurationRefusesAuthentication(): void {
		$this->withRequest(function(): void {
			$auth = $this->auth(null);
			$_REQUEST = ['login' => 1, 'username' => 'alice', 'password' => 'secret'];
			$this->assertNull($auth->login());
			$this->assertSame(['depending_services' => 'Fail'], $auth->checkDependencies());
		});
	}

	public function testEmptyUserListRefusesAuthentication(): void {
		$this->withRequest(function(): void {
			$auth = $this->auth(['user' => [], 'pass' => []]);
			$_REQUEST = ['login' => 1, 'username' => 'alice', 'password' => 'secret'];
			$this->assertNull($auth->login());
			$this->assertSame(['depending_services' => 'Ok'], $auth->checkDependencies());
		});
	}

	public function testSecondUserCanAuthenticateUsingMatchingHash(): void {
		$this->withRequest(function(): void {
			$auth = $this->auth(['user' => ['alice', 'bob'], 'pass' => [sha1('first'), sha1('second')]]);
			$_REQUEST = ['login' => 1, 'username' => 'bob', 'password' => 'second'];
			$this->assertSame('bob', $auth->login());
			$this->assertSame('multiauth', $auth::getName());
		});
	}

	public function testWrongPasswordDoesNotMatchAnotherUser(): void {
		$this->withRequest(function(): void {
			$auth = $this->auth(['user' => ['alice', 'bob'], 'pass' => [sha1('first'), sha1('second')]]);
			$_REQUEST = ['login' => 1, 'username' => 'bob', 'password' => 'first'];
			$this->assertNull($auth->login());
		});
	}

	public function testUnknownUserIsRejected(): void {
		$this->withRequest(function(): void {
			$auth = $this->auth(['user' => ['alice'], 'pass' => [sha1('first')]]);
			$_REQUEST = ['login' => 1, 'username' => 'other', 'password' => 'first'];
			$this->assertNull($auth->login());
		});
	}

	public function testMissingRequestFieldsAreRejected(): void {
		$this->withRequest(function(): void {
			$auth = $this->auth(['user' => ['alice'], 'pass' => [sha1('first')]]);
			$_REQUEST = ['username' => 'alice', 'password' => 'first'];
			$this->assertNull($auth->login());
			$_REQUEST = ['login' => 1, 'username' => 'alice'];
			$this->assertNull($auth->login());
			$_REQUEST = ['login' => 1, 'password' => 'first'];
			$this->assertNull($auth->login());
		});
	}
}
