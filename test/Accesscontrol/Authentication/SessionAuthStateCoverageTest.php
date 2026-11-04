<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use Base3\Accesscontrol\Authentication\SessionAuth;
use Base3\Session\NoSession\NoSession;
use PHPUnit\Framework\TestCase;

final class SessionAuthStateCoverageTest extends TestCase {

	private function withSession(callable $callback): void {
		$originalSession = $_SESSION ?? null;
		$originalRequest = $_REQUEST;
		try {
			$_SESSION = [];
			$_REQUEST = [];
			$callback(new SessionAuth(new NoSession()));
		} finally {
			$_SESSION = $originalSession;
			$_REQUEST = $originalRequest;
		}
	}

	public function testMissingAuthenticationStateDoesNotLogin(): void {
		$this->withSession(function(SessionAuth $auth): void {
			$this->assertNull($auth->login());
			$_SESSION['authentication'] = [];
			$this->assertNull($auth->login());
		});
	}

	public function testKeepCreatesAuthenticationRecord(): void {
		$this->withSession(function(SessionAuth $auth): void {
			$auth->keep('alice');
			$this->assertSame(['authentication' => ['userid' => 'alice']], $_SESSION);
			$this->assertSame('alice', $auth->login());
		});
	}

	public function testKeepUpdatesExistingAuthenticationRecordWithoutLosingExtraFields(): void {
		$this->withSession(function(SessionAuth $auth): void {
			$_SESSION['authentication'] = ['userid' => 'old', 'method' => 'trusted'];
			$auth->keep('new');
			$this->assertSame(['userid' => 'new', 'method' => 'trusted'], $_SESSION['authentication']);
		});
	}

	public function testKeepIgnoresNullOrEmptyIdentity(): void {
		$this->withSession(function(SessionAuth $auth): void {
			$auth->keep(null);
			$auth->keep('');
			$this->assertSame([], $_SESSION);
		});
	}

	public function testLogoutRequiresExplicitRequest(): void {
		$this->withSession(function(SessionAuth $auth): void {
			$auth->keep('alice');
			$auth->logout();
			$this->assertSame('alice', $auth->login());
			$_REQUEST['logout'] = 'yes';
			$auth->logout();
			$this->assertNull($auth->login());
			$this->assertArrayNotHasKey('authentication', $_SESSION);
		});
	}

	public function testDependencyCheckReflectsSessionBackend(): void {
		$this->withSession(function(SessionAuth $auth): void {
			$this->assertSame(['session_started' => 'Fail'], $auth->checkDependencies());
		});
	}
}
