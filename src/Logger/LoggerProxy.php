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

