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

