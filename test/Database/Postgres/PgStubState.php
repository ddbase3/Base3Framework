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

namespace Base3\Core\Database\Postgres;

final class PgStubState {

	public static bool $connectOk = true;
	public static string $lastError = '';
	public static int $affectedRows = 0;

	/** @var array<int, mixed> */
	public static array $queryQueue = [];

	public static function reset(): void {
		self::$connectOk = true;
		self::$lastError = '';
		self::$affectedRows = 0;
		self::$queryQueue = [];
	}
}
