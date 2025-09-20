<?php declare(strict_types=1);

namespace Base3\Logger;

use Base3\Logger\Api\ILogger;

/**
 * LoggerBridgeTrait
 *
 * Provides PSR-3 convenience methods and legacy log(scope, ...) mapping.
 * Requires the using class to implement logLevel().
 */
trait LoggerBridgeTrait {

	public function emergency(string|\Stringable $message, array $context = []): void {
		$this->logLevel(ILogger::EMERGENCY, $message, $context);
	}

	public function alert(string|\Stringable $message, array $context = []): void {
		$this->logLevel(ILogger::ALERT, $message, $context);
	}

	public function critical(string|\Stringable $message, array $context = []): void {
		$this->logLevel(ILogger::CRITICAL, $message, $context);
	}

	public function error(string|\Stringable $message, array $context = []): void {
		$this->logLevel(ILogger::ERROR, $message, $context);
	}

	public function warning(string|\Stringable $message, array $context = []): void {
		$this->logLevel(ILogger::WARNING, $message, $context);
	}

	public function notice(string|\Stringable $message, array $context = []): void {
		$this->logLevel(ILogger::NOTICE, $message, $context);
	}

	public function info(string|\Stringable $message, array $context = []): void {
		$this->logLevel(ILogger::INFO, $message, $context);
	}

	public function debug(string|\Stringable $message, array $context = []): void {
		$this->logLevel(ILogger::DEBUG, $message, $context);
	}

	public function log(string $scope, string $log, ?int $timestamp = null): bool {
		$ctx = [
			'scope'     => $scope,
			'timestamp' => $timestamp ?? time()
		];
		$this->logLevel(ILogger::INFO, $log, $ctx);
		return true;
	}

	public function getScopes(): array {
		return [];
	}

	public function getNumOfScopes() {
		return 0;
	}

	public function getLogs(string $scope, int $num = 50, bool $reverse = true): array {
		return [];
	}
}

