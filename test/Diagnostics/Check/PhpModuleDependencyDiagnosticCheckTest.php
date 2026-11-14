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

use Base3\Core\Diagnostics\Check\PhpModuleDependencyDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
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
