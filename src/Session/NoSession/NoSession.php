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

namespace Base3\Session\NoSession;

use Base3\Session\Api\ISession;

class NoSession implements ISession {

	public function started(): bool {
		return false;
	}

	public function getId(): string {
		return '';
	}

	public function start(): bool {
		return false;
	}

	public function destroy(): bool {
		return false;
	}

	public function get(string $key, mixed $default = null): mixed {
		return $default;
	}

	public function set(string $key, mixed $value): void {
		// no-op
	}

	public function has(string $key): bool {
		return false;
	}

	public function remove(string $key): void {
		// no-op
	}
}

