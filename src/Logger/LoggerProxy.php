<?php declare(strict_types=1);

namespace Base3\Logger;

use Base3\Api\ICheck;
use Base3\Logger\Api\ILogger;

/**
 * Class LoggerProxy
 *
 * Acts as a proxy to an underlying ILogger implementation.
 * Delegates all calls to the injected connector.
 */
class LoggerProxy implements ILogger, ICheck {

	private $connector;

	/**
	 * @param ILogger $connector Underlying logger instance
	 */
	public function __construct($connector) {
		$this->connector = $connector;
	}

	// -----------------------------------------------------
	// PSR-3 style methods
	// -----------------------------------------------------

	/**
	 * {@inheritdoc}
	 */
	public function emergency(string|\Stringable $message, array $context = []): void {
		$this->connector->emergency($message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function alert(string|\Stringable $message, array $context = []): void {
		$this->connector->alert($message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function critical(string|\Stringable $message, array $context = []): void {
		$this->connector->critical($message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function error(string|\Stringable $message, array $context = []): void {
		$this->connector->error($message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function warning(string|\Stringable $message, array $context = []): void {
		$this->connector->warning($message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function notice(string|\Stringable $message, array $context = []): void {
		$this->connector->notice($message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function info(string|\Stringable $message, array $context = []): void {
		$this->connector->info($message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function debug(string|\Stringable $message, array $context = []): void {
		$this->connector->debug($message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function logLevel(string $level, string|\Stringable $message, array $context = []): void {
		$this->connector->logLevel($level, $message, $context);
	}

	// -----------------------------------------------------
	// Legacy methods
	// -----------------------------------------------------

	/**
	 * {@inheritdoc}
	 */
	public function log(string $scope, string $log, ?int $timestamp = null): bool {
		return $this->connector->log($scope, $log, $timestamp);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getScopes(): array {
		return $this->connector->getScopes();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNumOfScopes() {
		return $this->connector->getNumOfScopes();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLogs(string $scope, int $num = 50, bool $reverse = true): array {
		return $this->connector->getLogs($scope, $num, $reverse);
	}

	// -----------------------------------------------------
	// ICheck
	// -----------------------------------------------------

	/**
	 * {@inheritdoc}
	 */
	public function checkDependencies() {
		return $this->connector instanceof ICheck ? $this->connector->checkDependencies() : [];
	}
}

