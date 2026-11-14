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

use Base3\Core\Accesscontrol\Authentication\SingleSignOnAutoAuth;
use Base3\CoreFoundation\Api\IContainer;
use Base3\Core\Configuration\ArrayConfiguration;
use Base3\Core\ServiceLocator;
use Base3\CoreFoundation\Session\Api\ISession;
use Base3\Core\Session\NoSession\NoSession;
use PHPUnit\Framework\TestCase;

final class SingleSignOnAutoGuardsCoverageTest extends TestCase {

	private function withGuard(ISession $session, bool $loginpage, callable $run): void {
		$previousServer = $_SERVER;
		$previousSession = $_SESSION ?? null;
		try {
			$_SERVER = [];
			$_SESSION = [];
			$container = new ServiceLocator();
			if ($loginpage) $container->set('loginpage', new \stdClass(), IContainer::SHARED);
			$run(new SingleSignOnAutoAuth(new ArrayConfiguration(), $session, $container));
		} finally { $_SERVER = $previousServer; $_SESSION = $previousSession; }
	}

	public function testAuthenticatedUserNeverTriggersSSOCheck(): void {
		$this->withGuard(new StartedSessionFixture(), true, function(SingleSignOnAutoAuth $auth): void {
			$auth->finish('member');
			$this->assertSame([], $_SESSION);
			$this->assertSame('singlesignonautoauth', $auth::getName());
		});
	}

	public function testPostRequestDoesNotRedirectAnonymousUser(): void {
		$this->withGuard(new StartedSessionFixture(), true, function(SingleSignOnAutoAuth $auth): void {
			$_SERVER['REQUEST_METHOD'] = 'POST';
			$auth->finish(null);
			$this->assertSame([], $_SESSION);
		});
	}

	public function testAjaxRequestDoesNotRedirectAnonymousUser(): void {
		$this->withGuard(new StartedSessionFixture(), true, function(SingleSignOnAutoAuth $auth): void {
			$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
			$auth->finish(null);
			$this->assertSame([], $_SESSION);
		});
	}

	public function testRecentSSOAttemptIsNotRepeatedWithinMinute(): void {
		$this->withGuard(new StartedSessionFixture(), true, function(SingleSignOnAutoAuth $auth): void {
			$timestamp = time();
			$_SESSION['ssocheck'] = $timestamp;
			$auth->finish(null);
			$this->assertSame(['ssocheck' => $timestamp], $_SESSION);
		});
	}

	public function testUnavailableSessionPreventsAutomaticRedirect(): void {
		$this->withGuard(new NoSession(), true, function(SingleSignOnAutoAuth $auth): void {
			$auth->finish(null);
			$this->assertSame(['depending_services' => 'Ok'], $auth->checkDependencies());
			$this->assertSame([], $_SESSION);
		});
	}

	public function testMissingLoginpageIsReportedWithoutTryingToRedirect(): void {
		$this->withGuard(new StartedSessionFixture(), false, function(SingleSignOnAutoAuth $auth): void {
			$this->assertSame(['depending_services' => 'Fail'], $auth->checkDependencies());
			$auth->finish('already-authenticated');
			$this->assertSame([], $_SESSION);
		});
	}
}

require_once __DIR__ . '/StartedSessionFixture.php';
