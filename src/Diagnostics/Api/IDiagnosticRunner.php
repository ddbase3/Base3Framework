<?php declare(strict_types=1);

namespace Base3\Diagnostics\Api;

use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use InvalidArgumentException;

/**
 * Shared discovery and execution boundary for classmap-defined diagnostics.
 *
 * Permission enforcement belongs to the invoking endpoint. The runner never
 * grants access or exposes an HTTP endpoint on its own.
 */
interface IDiagnosticRunner {

	/**
	 * Discovers scopes across all installed modules, ordered by priority/name.
	 *
	 * @return list<IDiagnosticScope> Discovered scope definitions
	 */
	public function getScopes(): array;

	/**
	 * Lists registered checks belonging to the specified scope, without running them.
	 *
	 * Only installed classmap instances are considered; legacy ICheck instances
	 * are never inspected. Empty scopes return an empty array.
	 *
	 * @param string $scope Technical IDiagnosticScope::getName() identifier
	 * @return list<IDiagnosticCheck> Checks ordered by their technical name
	 * @throws InvalidArgumentException When the scope is unknown
	 */
	public function getChecks(string $scope): array;

	/**
	 * Executes exactly one check within the specified registered scope.
	 *
	 * Unexpected runtime errors become an ERROR result; the exception message
	 * is deliberately not included. No other checks execute as a side effect.
	 *
	 * @param string $scope Technical scope name, never an arbitrary class name
	 * @param string $name Technical IDiagnosticCheck::getName() identifier
	 * @return DiagnosticCheckResult Evaluated result, including controlled errors
	 * @throws InvalidArgumentException On unknown scope, check or scope mismatch
	 */
	public function run(string $scope, string $name): DiagnosticCheckResult;

}
