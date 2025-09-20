<?php declare(strict_types=1);

namespace Base3\Logger;

use Base3\Logger\Api\ILogger;

/**
 * AbstractLogger
 *
 * Base implementation of ILogger using LoggerBridgeTrait.
 * Concrete loggers must implement logLevel().
 */
abstract class AbstractLogger implements ILogger {
	use LoggerBridgeTrait;

	/**
	 * Logs with an arbitrary level.
	 *
	 * This method must be implemented by concrete loggers.
	 *
	 * @param string $level One of the ILogger::* constants
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data
	 * @return void
	 */
	abstract public function logLevel(string $level, string|\Stringable $message, array $context = []): void;
}

