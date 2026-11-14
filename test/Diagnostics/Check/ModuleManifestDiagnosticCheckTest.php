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

use Base3\Core\Diagnostics\Check\ModuleManifestDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
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
