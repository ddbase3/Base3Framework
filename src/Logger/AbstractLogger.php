<?php declare(strict_types=1);

namespace Base3\Logger;

use Base3\Logger\Api\ILogger;

/**
 * Class AbstractLogger
 *
 * Base implementation of the ILogger interface.
 * Provides default implementations of all level-specific methods
 * that forward to logLevel().
 *
 * Concrete logger implementations must implement logLevel().
 */
abstract class AbstractLogger implements ILogger {

	/**
	 * Logs with an arbitrary level.
	 *
	 * This method must be implemented by concrete loggers.
	 *
	 * @param string $level One of the ILogger::* level constants
	 * @param string|\Stringable $message The log message
	 * @param array<string,mixed> $context Contextual data for interpolation
	 * @return void
	 */
	abstract public function logLevel(string $level, string|\Stringable $message, array $context = []): void;

	/**
	 * {@inheritdoc}
	 */
	public function emergency(string|\Stringable $message, array $context = []): void {
		$this->logLevel(self::EMERGENCY, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function alert(string|\Stringable $message, array $context = []): void {
		$this->logLevel(self::ALERT, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function critical(string|\Stringable $message, array $context = []): void {
		$this->logLevel(self::CRITICAL, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function error(string|\Stringable $message, array $context = []): void {
		$this->logLevel(self::ERROR, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function warning(string|\Stringable $message, array $context = []): void {
		$this->logLevel(self::WARNING, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function notice(string|\Stringable $message, array $context = []): void {
		$this->logLevel(self::NOTICE, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function info(string|\Stringable $message, array $context = []): void {
		$this->logLevel(self::INFO, $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function debug(string|\Stringable $message, array $context = []): void {
		$this->logLevel(self::DEBUG, $message, $context);
	}

	// -----------------------------------------------------
	// Legacy methods from ILogger
	// -----------------------------------------------------

	/**
	 * {@inheritdoc}
	 */
	public function log(string $scope, string $log, ?int $timestamp = null): bool {
		$ctx = [
			'scope'     => $scope,
			'timestamp' => $timestamp ?? time()
		];
		$this->logLevel(self::INFO, $log, $ctx);
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getScopes(): array {
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNumOfScopes() {
		return 0;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLogs(string $scope, int $num = 50, bool $reverse = true): array {
		return [];
	}
}

