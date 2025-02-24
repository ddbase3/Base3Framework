<?php declare(strict_types=1);

namespace Logger\Api;

interface ILogger {

	public function log(string $scope, string $log, $timestamp = null): bool;
	public function getScopes(): array;
	public function getNumOfScopes();
	public function getLogs(string $scope, int $num = 50, bool $reverse = true): array;

}
