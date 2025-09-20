<?php declare(strict_types=1);

namespace Base3\Logger\Api;

/**
 * Interface ILogger
 *
 * Unified logging interface that combines PSR-3 compatibility
 * with project-specific scope-based logging methods.
 */
interface ILogger {

	// -----------------------------------------------------
	// PSR-3 Log Levels
	// -----------------------------------------------------

	/** System is unusable. */
	public const EMERGENCY = 'emergency';

	/** Action must be taken immediately. */
	public const ALERT = 'alert';

	/** Critical conditions. */
	public const CRITICAL = 'critical';

	/** Runtime errors that do not require immediate action but should be logged. */
	public const ERROR = 'error';

	/** Exceptional occurrences that are not errors. */
	public const WARNING = 'warning';

	/** Normal but significant events. */
	public const NOTICE = 'notice';

	/** Interesting events, like user logins or SQL logs. */
	public const INFO = 'info';

	/** Detailed debug information. */
	public const DEBUG = 'debug';

	// -----------------------------------------------------
	// PSR-3 Style Logging Methods
	// -----------------------------------------------------

	/**
	 * Logs an emergency message.
	 *
	 * Example: The system is down, no recovery possible.
	 *
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data for interpolation
	 * @return void
	 */
	public function emergency(string|\Stringable $message, array $context = []): void;

	/**
	 * Logs an alert message.
	 *
	 * Example: Database unavailable, immediate action required.
	 *
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data for interpolation
	 * @return void
	 */
	public function alert(string|\Stringable $message, array $context = []): void;

	/**
	 * Logs a critical condition.
	 *
	 * Example: Application component failure, unexpected exception.
	 *
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data for interpolation
	 * @return void
	 */
	public function critical(string|\Stringable $message, array $context = []): void;

	/**
	 * Logs an error condition.
	 *
	 * Example: Runtime errors that do not require immediate action
	 * but should be logged and monitored.
	 *
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data for interpolation
	 * @return void
	 */
	public function error(string|\Stringable $message, array $context = []): void;

	/**
	 * Logs a warning message.
	 *
	 * Example: Deprecated API usage, undesirable but not fatal behavior.
	 *
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data for interpolation
	 * @return void
	 */
	public function warning(string|\Stringable $message, array $context = []): void;

	/**
	 * Logs a notice message.
	 *
	 * Example: Normal but significant events.
	 *
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data for interpolation
	 * @return void
	 */
	public function notice(string|\Stringable $message, array $context = []): void;

	/**
	 * Logs an informational message.
	 *
	 * Example: User login, query executed.
	 *
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data for interpolation
	 * @return void
	 */
	public function info(string|\Stringable $message, array $context = []): void;

	/**
	 * Logs a debug message.
	 *
	 * Example: Detailed debug information for development.
	 *
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data for interpolation
	 * @return void
	 */
	public function debug(string|\Stringable $message, array $context = []): void;

	/**
	 * Logs with an arbitrary level.
	 *
	 * This method is a PSR-3 style generic log entry point.
	 *
	 * @param string $level One of the ILogger::* level constants
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data for interpolation
	 * @return void
	 */
	public function logLevel(string $level, string|\Stringable $message, array $context = []): void;

	// -----------------------------------------------------
	// Legacy Project-Specific Logging Methods
	// -----------------------------------------------------

	/**
	 * Writes a log entry to the specified scope.
	 *
	 * @deprecated Use PSR-3 style log methods instead.
	 *
	 * @param string   $scope     Logical log group or category
	 * @param string   $log       Log message
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
	 * @param string $scope  The log scope to retrieve from
	 * @param int    $num    Maximum number of log entries (default: 50)
	 * @param bool   $reverse Whether to return logs in reverse chronological order (default: true)
	 * @return array<int, array<string, mixed>> List of logs, each log as associative array
	 */
	public function getLogs(string $scope, int $num = 50, bool $reverse = true): array;

	// -----------------------------------------------------
	// Optional: Placeholder for future transition
	// -----------------------------------------------------

	/*
	/**
	 * Future PSR-3 compliant log() method.
	 *
	 * @param string $level One of the ILogger::* level constants
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data for interpolation
	 * @return void
	 * /
	// public function log(string $level, string|\Stringable $message, array $context = []): void;
	*/
}

