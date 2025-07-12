<?php declare(strict_types=1);

namespace Base3\Logger\Api;

/**
 * Interface ILogger
 *
 * Defines a logging interface for scoped messages with optional timestamps.
 */
interface ILogger {

	/**
	 * Writes a log entry to the specified scope.
	 *
	 * @param string $scope Logical log group or category
	 * @param string $log Log message
	 * @param int|null $timestamp Optional UNIX timestamp (default: current time)
	 * @return bool True on success, false on failure
	 */
	public function log(string $scope, string $log, ?int $timestamp = null): bool;

	/**
	 * Returns all available logging scopes.
	 *
	 * @return array<string> List of scope names
	 */
	public function getScopes(): array;

	/**
	 * Returns the number of available scopes.
	 *
	 * @return int Scope count
	 */
	public function getNumOfScopes();

	/**
	 * Retrieves a list of log entries for the given scope.
	 *
	 * @param string $scope The log scope to retrieve from
	 * @param int $num Maximum number of log entries (default: 50)
	 * @param bool $reverse Whether to return logs in reverse chronological order (default: true)
	 * @return array<int, array<string, mixed>> List of logs, each log as associative array
	 */
	public function getLogs(string $scope, int $num = 50, bool $reverse = true): array;

}

