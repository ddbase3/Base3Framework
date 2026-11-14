<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Diagnostics\Check;

use Base3\Core\Diagnostics\Check\ClassMapSourceDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
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
