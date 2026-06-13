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

