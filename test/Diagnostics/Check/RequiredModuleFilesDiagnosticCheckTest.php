<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Check;

use Base3\Diagnostics\Check\RequiredModuleFilesDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use PHPUnit\Framework\TestCase;

final class RequiredModuleFilesDiagnosticCheckTest extends TestCase {

	private DiagnosisSourceFixture $fixture;

	protected function setUp(): void {
		$this->fixture = new DiagnosisSourceFixture();
	}

	protected function tearDown(): void {
		$this->fixture->cleanup();
	}

	private function runCheck(): DiagnosticCheckResult {
		return (new RequiredModuleFilesDiagnosticCheck(
			$this->fixture->registry(),
			$this->fixture->frameworkRoot,
			$this->fixture->pluginsRoot
		))->run();
	}

	private function writeRequiredFiles(string $root): void {
		foreach(['README.md', 'PRIVACY.md', 'LICENSE'] as $filename) {
			file_put_contents($root . '/' . $filename, 'Present');
		}
	}

	public function testPassesWhenEveryManifestModuleHasAllThreeFiles(): void {
		$this->writeRequiredFiles($this->fixture->module('Alpha', 'Alpha'));
		$this->writeRequiredFiles($this->fixture->module('Beta', 'Beta'));
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::PASSED, $result->status);
		$this->assertSame(2, $result->details['inspected']);
	}

	public function testReportsMissingDocumentsPerModule(): void {
		$root = $this->fixture->module('Alpha', 'Alpha');
		file_put_contents($root . '/README.md', 'Present');
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertSame(2, $result->details['failed']);
		$this->assertStringContainsString('Alpha: missing PRIVACY.md', $result->replacements['sample']);
		$this->assertStringContainsString('Alpha: missing LICENSE', $result->replacements['sample']);
	}

	public function testFrameworkRootIsCheckedAsAModule(): void {
		$this->fixture->writeManifest('Alpha', [
			'manifestVersion' => 1, 'name' => 'Alpha', 'namespace' => 'Alpha', 'version' => '1.0.0'
		]);
		$this->writeRequiredFiles($this->fixture->pluginsRoot . '/Alpha');
		file_put_contents($this->fixture->frameworkRoot . '/base3.json', json_encode([
			'manifestVersion' => 1, 'name' => 'Base3Framework', 'namespace' => 'Base3', 'version' => '4.10.0'
		], JSON_THROW_ON_ERROR));
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertSame(2, $result->details['inspected']);
		$this->assertStringContainsString('framework: missing LICENSE', $result->replacements['sample']);
	}

	public function testMissingManifestCannotSilentlyPass(): void {
		$root = $this->fixture->module('Alpha', 'Alpha');
		$this->writeRequiredFiles($root);
		unlink($root . '/base3.json');
		$this->assertSame(DiagnosticCheckResult::FAILED, $this->runCheck()->status);
	}

}
