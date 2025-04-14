<?php declare(strict_types=1);

namespace Base3\Logger;

use Base3\Api\ICheck;
use Base3\Logger\Api\ILogger;

class LoggerProxy implements ILogger, ICheck {

	private $connector;

	public function __construct($connector) {
		$this->connector = $connector;
	}

	public function log(string $scope, string $log, ?int $timestamp = null): bool {
		return $this->connector->log($scope, $log, $timestamp);
	}

	public function getScopes(): array {
		return $this->connector->getScopes();
	}

	public function getNumOfScopes() {
		return $this->connector->getNumOfScopes();
	}

	public function getLogs(string $scope, int $num = 50, bool $reverse = true): array {
		return $this->connector->getLogs($scope, $num, $reverse);
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return $this->connector instanceof ICheck ? $this->connector->checkDependencies() : [];
	}
}
