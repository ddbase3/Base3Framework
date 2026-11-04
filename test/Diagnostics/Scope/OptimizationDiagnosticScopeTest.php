<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Scope;

use PHPUnit\Framework\TestCase;

final class OptimizationDiagnosticScopeTest extends TestCase {

	public function testExposesStableDiagnosticsMetadata(): void {
		$scope = new \Base3\Diagnostics\Scope\OptimizationDiagnosticScope();
		$this->assertSame('optimizationdiagnosticscope', $scope::getName());
		$this->assertSame(40, $scope->getPriority());
		$this->assertSame('diagnosticscopedisplay', $scope->getDisplayName());
	}

}
