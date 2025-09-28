<?php declare(strict_types=1);

namespace Base3\Session\PhpSession;

use Base3\Session\AbstractSession;

class PhpSession extends AbstractSession {

	public function start(): bool {
		if ($this->isStarted) {
			return true;
		}

		if (PHP_SAPI === 'cli') {
			return false;
		}

		if (session_status() === PHP_SESSION_NONE) {
			if (!session_start()) {
				return false;
			}
		}

		$this->isStarted = true;
		return true;
	}
}

