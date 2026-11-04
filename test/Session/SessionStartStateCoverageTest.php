<?php declare(strict_types=1);

namespace Base3\Test\Session;

use Base3\Configuration\ArrayConfiguration;
use Base3\Session\AbstractSession;
use Base3\Session\BasicSession\BasicSession;
use Base3\Session\DomainSession\DomainSession;
use Base3\Session\PhpSession\PhpSession;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class SessionStartStateCoverageTest extends TestCase {

	#[RunInSeparateProcess]
	public function testBasicSessionDoesNotAdoptAnExternalCliSessionAndHonorsStartedFlag(): void {
		$this->withNativeSession(function(): void {
			$session = new BasicSession(new ArrayConfiguration());
			$this->assertFalse($session->start());
			$this->assertFalse($session->started());

			$this->markStarted($session);
			$this->assertTrue($session->start());
			$this->assertTrue($session->started());
			$this->assertSame(session_id(), $session->getId());
		});
	}

	#[RunInSeparateProcess]
	public function testDomainSessionDoesNotChangeTheCookieDomainInCliAndHonorsStartedFlag(): void {
		$this->withNativeSession(function(): void {
			$previousDomain = (string)ini_get('session.cookie_domain');
			$configuration = new ArrayConfiguration(['session' => ['cookiedomain' => 'other.example']]);
			$session = new DomainSession($configuration);

			$this->assertFalse($session->start());
			$this->assertSame($previousDomain, (string)ini_get('session.cookie_domain'));
			$this->assertFalse($session->started());

			$this->markStarted($session);
			$this->assertTrue($session->start());
			$this->assertTrue($session->started());
			$this->assertSame($previousDomain, (string)ini_get('session.cookie_domain'));
		});
	}

	#[RunInSeparateProcess]
	public function testPhpSessionHonorsStartedFlagWithoutStartingAnotherNativeSession(): void {
		$this->withNativeSession(function(): void {
			$session = new PhpSession();
			$this->assertFalse($session->start());
			$this->assertFalse($session->started());

			$this->markStarted($session);
			$this->assertTrue($session->start());
			$this->assertTrue($session->started());
			$this->assertSame(session_id(), $session->getId());
		});
	}

	private function markStarted(AbstractSession $session): void {
		$property = new ReflectionProperty(AbstractSession::class, 'isStarted');
		$property->setValue($session, true);
	}

	private function withNativeSession(callable $assertions): void {
		$previousCookieSetting = (string)ini_get('session.use_cookies');
		$previousSessionId = session_id();
		ini_set('session.use_cookies', '0');

		try {
			session_id('base3test' . bin2hex(random_bytes(8)));
			$this->assertTrue(session_start());
			$assertions();
		} finally {
			if (session_status() === PHP_SESSION_ACTIVE) {
				session_destroy();
			}
			$_SESSION = [];
			ini_set('session.use_cookies', $previousCookieSetting);
			session_id($previousSessionId);
		}
	}
}
