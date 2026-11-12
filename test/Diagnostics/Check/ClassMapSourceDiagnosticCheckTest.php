<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Check;

use Base3\Diagnostics\Check\ClassMapSourceDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use PHPUnit\Framework\TestCase;

final class ClassMapSourceDiagnosticCheckTest extends TestCase {

	private DiagnosisSourceFixture $fixture;

	protected function setUp(): void {
		$this->fixture = new DiagnosisSourceFixture();
	}

	protected function tearDown(): void {
		$this->fixture->cleanup();
	}

	private function runCheck(): DiagnosticCheckResult {
		return (new ClassMapSourceDiagnosticCheck(
			$this->fixture->registry(),
			$this->fixture->frameworkRoot,
			$this->fixture->pluginsRoot
		))->run();
	}

	public function testAcceptsCorrectlyNamedSource(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Service/AlphaService.php' => '<?php namespace Alpha\\Service; final class AlphaService { public static function getName(): string { return "alphaservice"; } }'
		]);
		$this->assertSame(DiagnosticCheckResult::PASSED, $this->runCheck()->status);
	}

	public function testRejectsIncorrectNamespaceAndDuplicateTechnicalNames(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Service/AlphaService.php' => '<?php namespace Wrong; final class AlphaService { public static function getName(): string { return "repeated"; } }',
			'Service/BetaService.php' => '<?php namespace Alpha\\Service; final class BetaService { public static function getName(): string { return "repeated"; } }'
		]);
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertGreaterThanOrEqual(2, $result->details['failed']);
	}

}
