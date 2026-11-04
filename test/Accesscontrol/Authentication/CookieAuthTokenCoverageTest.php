<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use Base3\Accesscontrol\Authentication\CookieAuth;
use Base3\Api\IContainer;
use Base3\Core\ServiceLocator;
use Base3\Test\Accesscontrol\RecordingAuthToken;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class CookieAuthTokenCoverageTest extends TestCase {

	private function withAuth(callable $run): void {
		$locator = ServiceLocator::getInstance();
		$cookie = $_COOKIE;
		$request = $_REQUEST;
		try {
			$container = new ServiceLocator();
			$token = new RecordingAuthToken();
			$container->set('classmap', new \stdClass(), IContainer::SHARED);
			$container->set('authtoken', $token, IContainer::SHARED);
			ServiceLocator::useInstance($container);
			$_COOKIE = [];
			$_REQUEST = [];
			$run(new CookieAuth(), $token);
		} finally { ServiceLocator::useInstance($locator); $_COOKIE = $cookie; $_REQUEST = $request; }
	}

	public function testMissingCookieReturnsAnonymousIdentity(): void {
		$this->withAuth(function(CookieAuth $auth, RecordingAuthToken $token): void {
			$this->assertNull($auth->login());
			$this->assertSame(['depending_services' => 'Ok'], $auth->checkDependencies());
			$this->assertSame([], $token->deleted);
		});
	}

	public function testValidCookieRequiresMatchingStoredToken(): void {
		$this->withAuth(function(CookieAuth $auth, RecordingAuthToken $token): void {
			$_COOKIE['authentication'] = json_encode(['userid' => 'alice', 'token' => 'correct']);
			$token->valid = true;
			$this->assertSame('alice', $auth->login());
			$token->valid = false;
			$this->assertNull($auth->login());
		});
	}

	public function testKeepWithNullIdentityDoesNotCreateToken(): void {
		$this->withAuth(function(CookieAuth $auth, RecordingAuthToken $token): void {
			$auth->keep(null);
			$this->assertSame([], $token->deleted);
			$this->assertSame([], $token->saves);
		});
	}

	public function testKeepRevokesPreviousTokenBeforeIssuingNewOne(): void {
		$this->withAuth(function(CookieAuth $auth, RecordingAuthToken $token): void {
			$_COOKIE['authentication'] = json_encode(['userid' => 'old', 'token' => 'expired']);
			$auth->keep('new');
			$this->assertSame([['authentication', 'old', 'expired']], $token->deleted);
		});
	}

	public function testLogoutRequiresExplicitRequest(): void {
		$this->withAuth(function(CookieAuth $auth, RecordingAuthToken $token): void {
			$_COOKIE['authentication'] = json_encode(['userid' => 'alice', 'token' => 'secret']);
			$auth->logout();
			$this->assertArrayHasKey('authentication', $_COOKIE);
			$this->assertSame([], $token->deleted);
			$_REQUEST['logout'] = '1';
			$auth->logout();
			$this->assertArrayNotHasKey('authentication', $_COOKIE);
			$this->assertSame([['authentication', 'alice', 'secret']], $token->deleted);
		});
	}
}

require_once __DIR__ . '/../RecordingAuthToken.php';
