<?php declare(strict_types=1);

namespace Base3\Test\Session;

use Base3\Session\AbstractSession;

/** Simulates a native PHP session closing between the two destroy() checks. */
final class SessionClosedAfterCheckFixture extends AbstractSession {

	public function start(): bool {
		return false;
	}

	public function started(): bool {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			return false;
		}

		session_write_close();
		return true;
	}
}
