<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use Base3\Accesscontrol\Authentication\MultiAuth;
use Base3\Api\IContainer;
use Base3\Configuration\ArrayConfiguration;
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
