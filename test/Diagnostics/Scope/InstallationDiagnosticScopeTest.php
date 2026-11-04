<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Scope;

use PHPUnit\Framework\TestCase;

final class InstallationDiagnosticScopeTest extends TestCase {

	public function testExposesStableDiagnosticsMetadata(): void {
		$scope = new \Base3\Diagnostics\Scope\InstallationDiagnosticScope();
		$this->assertSame('installationdiagnosticscope', $scope::getName());
		$this->assertSame(20, $scope->getPriority());
		$this->assertSame('diagnosticscopedisplay', $scope->getDisplayName());
	}

}
