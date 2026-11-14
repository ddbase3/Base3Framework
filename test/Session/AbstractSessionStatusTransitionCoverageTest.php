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

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SessionClosedAfterCheckFixture.php';

final class AbstractSessionStatusTransitionCoverageTest extends TestCase {

	#[RunInSeparateProcess]
	public function testDestroyRefusesToDestroyAnAlreadyClosedNativeSession(): void {
		$previousCookieSetting = (string)ini_get('session.use_cookies');
		$previousSessionId = session_id();
		ini_set('session.use_cookies', '0');

		try {
			session_id('base3test' . bin2hex(random_bytes(8)));
			$this->assertTrue(session_start());
			$_SESSION['stored'] = 'value';

			$session = new SessionClosedAfterCheckFixture();
			$this->assertFalse($session->destroy());
			$this->assertSame(PHP_SESSION_NONE, session_status());
			$this->assertSame([], $_SESSION);
		} finally {
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}
			if (session_status() === PHP_SESSION_ACTIVE) {
				session_destroy();
			}
			$_SESSION = [];
			ini_set('session.use_cookies', $previousCookieSetting);
			session_id($previousSessionId);
		}
	}
}
