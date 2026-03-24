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

namespace Base3\Session;

use Base3\Session\Api\ISession;

/**
 * AbstractSession
 *
 * Provides a base implementation for ISession with common logic
 * for identifier handling, storage access and destruction.
 * Subclasses must implement start().
 */
abstract class AbstractSession implements ISession {

	protected bool $isStarted = false;

	/**
	 * Starts the session if not already active.
	 *
	 * Must be implemented by subclasses.
	 *
	 * @return bool True if session was started successfully
	 */
	abstract public function start(): bool;

	public function started(): bool {
		return $this->isStarted && session_status() === PHP_SESSION_ACTIVE;
	}

	public function getId(): string {
		return $this->started() ? session_id() : '';
	}

	public function destroy(): bool {
		if (!$this->started()) {
			return false;
		}
		$_SESSION = [];
		if (session_status() === PHP_SESSION_ACTIVE) {
			return session_destroy();
		}
		return false;
	}

	public function get(string $key, mixed $default = null): mixed {
		return $this->started() && array_key_exists($key, $_SESSION)
			? $_SESSION[$key]
			: $default;
	}

	public function set(string $key, mixed $value): void {
		if ($this->started()) {
			$_SESSION[$key] = $value;
		}
	}

	public function has(string $key): bool {
		return $this->started() && array_key_exists($key, $_SESSION);
	}

	public function remove(string $key): void {
		if ($this->started() && array_key_exists($key, $_SESSION)) {
			unset($_SESSION[$key]);
		}
	}
}

