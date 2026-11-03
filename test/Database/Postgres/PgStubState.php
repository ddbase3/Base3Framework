<?php declare(strict_types=1);

namespace Base3\Database\Postgres;

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
