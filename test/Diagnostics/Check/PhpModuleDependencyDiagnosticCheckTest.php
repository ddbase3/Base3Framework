<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Check;

use Base3\Diagnostics\Check\PhpModuleDependencyDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use PHPUnit\Framework\TestCase;

final class PhpModuleDependencyDiagnosticCheckTest extends TestCase {

	private DiagnosisSourceFixture $fixture;

	protected function setUp(): void {
		$this->fixture = new DiagnosisSourceFixture();
	}

	protected function tearDown(): void {
		$this->fixture->cleanup();
	}

	private function runCheck(): DiagnosticCheckResult {
		return (new PhpModuleDependencyDiagnosticCheck(
			$this->fixture->registry(),
			$this->fixture->frameworkRoot,
			$this->fixture->pluginsRoot
		))->run();
	}

	public function testRejectsUsedImportedTypesWithoutHardDependency(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Service/Example.php' => '<?php namespace Alpha\\Service; use Beta\\Api\\IBeta; final class Example { public function value(IBeta $beta): void {} }'
		]);
		$this->fixture->module('Beta', 'Beta', '1.0.0', [], [
			'Api/IBeta.php' => '<?php namespace Beta\\Api; interface IBeta {}'
		]);
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('missing manifest dependency', $result->replacements['sample']);
	}

	public function testRejectsMissingReferencedInterfaceEvenWithDependency(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', ['Beta' => '>=1.0.0'], [
			'Service/Example.php' => '<?php namespace Alpha\\Service; use Beta\\Api\\IBeta; final class Example { public function value(IBeta $beta): void {} }'
		]);
		$this->fixture->module('Beta', 'Beta', '1.0.0');
		$this->assertSame(DiagnosticCheckResult::FAILED, $this->runCheck()->status);
	}

	public function testAcceptsValidTypesWithDeclaredDependency(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', ['Beta' => '>=1.0.0'], [
			'Service/Example.php' => '<?php namespace Alpha\\Service; use Beta\\Api\\IBeta; final class Example { public function value(IBeta $beta): void {} }'
		]);
		$this->fixture->module('Beta', 'Beta', '1.0.0', [], [
			'Api/IBeta.php' => '<?php namespace Beta\\Api; interface IBeta {}'
		]);
		$this->assertSame(DiagnosticCheckResult::PASSED, $this->runCheck()->status);
	}

}
