<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Scope;

use PHPUnit\Framework\TestCase;

final class DependencyDiagnosticScopeTest extends TestCase {

	public function testExposesStableDiagnosticsMetadata(): void {
		$scope = new \Base3\Diagnostics\Scope\DependencyDiagnosticScope();
		$this->assertSame('dependencydiagnosticscope', $scope::getName());
		$this->assertSame(30, $scope->getPriority());
		$this->assertSame('diagnosticscopedisplay', $scope->getDisplayName());
	}

}
