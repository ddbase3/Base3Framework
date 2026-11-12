<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Check;

use Base3\Diagnostics\Check\ModuleManifestDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use PHPUnit\Framework\TestCase;

final class ModuleManifestDiagnosticCheckTest extends TestCase {

	private DiagnosisSourceFixture $fixture;

	protected function setUp(): void {
		$this->fixture = new DiagnosisSourceFixture();
	}

	protected function tearDown(): void {
		$this->fixture->cleanup();
	}

	private function runCheck(): DiagnosticCheckResult {
		return (new ModuleManifestDiagnosticCheck(
			$this->fixture->registry(),
			$this->fixture->frameworkRoot,
			$this->fixture->pluginsRoot
		))->run();
	}

	public function testChecksValidVersionDependencies(): void {
		$this->fixture->module('Base3Framework', 'Base3', '4.10.0');
		$this->fixture->module('ExamplePlugin', 'ExamplePlugin', '1.0.0', ['Base3Framework' => '^4.10']);
		$this->assertSame(DiagnosticCheckResult::PASSED, $this->runCheck()->status);
	}

	public function testFailsOnMissingManifestEvenWhenNotRegistered(): void {
		mkdir($this->fixture->pluginsRoot . '/UnregisteredPlugin');
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('Missing base3.json', $result->replacements['sample']);
	}

	public function testFailsOnInvalidManifestOrUnmetDependency(): void {
		$this->fixture->module('Base3Framework', 'Base3', '4.10.0');
		$this->fixture->module('ExamplePlugin', 'ExamplePlugin', '1.0.0', ['Base3Framework' => '>=5.0.0']);
		$this->assertSame(DiagnosticCheckResult::FAILED, $this->runCheck()->status);
		$this->fixture->writeManifest('ExamplePlugin', ['manifestVersion' => 1, 'name' => 'ExamplePlugin']);
		$this->assertSame(DiagnosticCheckResult::FAILED, $this->runCheck()->status);
	}

	public function testDetectsCycles(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', ['Beta' => '>=1.0.0']);
		$this->fixture->module('Beta', 'Beta', '1.0.0', ['Alpha' => '>=1.0.0']);
		$this->assertSame(DiagnosticCheckResult::FAILED, $this->runCheck()->status);
	}

}
