<?php declare(strict_types=1);

namespace Base3\Diagnostics;

use Base3\Api\IClassMap;
use Base3\Diagnostics\Api\IDiagnosticCheck;
use Base3\Diagnostics\Api\IDiagnosticRunner;
use Base3\Diagnostics\Api\IDiagnosticScope;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use InvalidArgumentException;
use Throwable;

/** Classmap owner of diagnostic discovery and single-check execution. */
final class DiagnosticRunner implements IDiagnosticRunner {

	public function __construct(private readonly IClassMap $classmap) {}

	public function getScopes(): array {
		$scopes = array_values(array_filter(
			$this->classmap->getInstancesByInterface(IDiagnosticScope::class),
			static fn($scope): bool => $scope instanceof IDiagnosticScope
		));
		usort($scopes, static fn(IDiagnosticScope $a, IDiagnosticScope $b): int =>
			($a->getPriority() <=> $b->getPriority()) ?: strcmp($a::getName(), $b::getName())
		);
		return $scopes;
	}

	public function getChecks(string $scope): array {
		$this->assertScope($scope);
		$checks = array_values(array_filter(
			$this->classmap->getInstancesByInterface(IDiagnosticCheck::class),
			static fn($check): bool => $check instanceof IDiagnosticCheck && $check->getScope() === $scope
		));
		usort($checks, static fn(IDiagnosticCheck $a, IDiagnosticCheck $b): int =>
			strcmp($a::getName(), $b::getName())
		);
		return $checks;
	}

	public function run(string $scope, string $name): DiagnosticCheckResult {
		$this->assertScope($scope);
		if($name === '') {
			throw new InvalidArgumentException('A diagnostic check name is required.');
		}
		$check = $this->classmap->getInstanceByInterfaceName(IDiagnosticCheck::class, $name);
		if(!$check instanceof IDiagnosticCheck || $check::getName() !== $name || $check->getScope() !== $scope) {
			throw new InvalidArgumentException('Unknown diagnostic check for this scope.');
		}
		try {
			return $check->run();
		} catch(Throwable $exception) {
			// Never expose potentially sensitive exception contents in a report.
			return new DiagnosticCheckResult(DiagnosticCheckResult::ERROR, 'error');
		}
	}

	private function assertScope(string $name): void {
		$scope = $this->classmap->getInstanceByInterfaceName(IDiagnosticScope::class, $name);
		if(!$scope instanceof IDiagnosticScope) {
			throw new InvalidArgumentException('Unknown diagnostic scope.');
		}
	}

}
