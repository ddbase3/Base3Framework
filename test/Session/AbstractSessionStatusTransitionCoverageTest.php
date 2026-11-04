<?php declare(strict_types=1);

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
