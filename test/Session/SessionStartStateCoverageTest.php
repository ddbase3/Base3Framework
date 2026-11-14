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

namespace Base3\Test\Session;

use Base3\Core\Configuration\ArrayConfiguration;
use Base3\CoreFoundation\Session\AbstractSession;
use Base3\Core\Session\BasicSession\BasicSession;
use Base3\Core\Session\DomainSession\DomainSession;
use Base3\Core\Session\PhpSession\PhpSession;
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
