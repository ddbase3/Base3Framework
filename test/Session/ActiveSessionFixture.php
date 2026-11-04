<?php declare(strict_types=1);

namespace Base3\Test\Session;

use Base3\Session\AbstractSession;

/** Exposes AbstractSession's state gate while using PHP's real session storage. */
final class ActiveSessionFixture extends AbstractSession {

	public function start(): bool {
		$this->isStarted = true;
		return session_status() === PHP_SESSION_ACTIVE;
	}
}
