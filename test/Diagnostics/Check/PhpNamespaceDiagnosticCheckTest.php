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

use Base3\Core\Diagnostics\Check\PhpNamespaceDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
use PHPUnit\Framework\TestCase;

final class PhpNamespaceDiagnosticCheckTest extends TestCase {

	private DiagnosisSourceFixture $fixture;

	protected function setUp(): void {
		$this->fixture = new DiagnosisSourceFixture();
	}

	protected function tearDown(): void {
		$this->fixture->cleanup();
	}

	private function check(): DiagnosticCheckResult {
		return (new PhpNamespaceDiagnosticCheck(
			$this->fixture->registry(),
			$this->fixture->frameworkRoot,
			$this->fixture->pluginsRoot,
			$this->fixture->root . '/unused-tests'
		))->run();
	}

	public function testAcceptsMatchingNamespaceAndManifest(): void {
		$this->fixture->module('Alpha', 'Base3\Alpha', '1.0.0', [], [
			'Service/Example.php' => '<?php namespace Base3\\Alpha\\Service; final class Example {}'
		]);
		$this->assertSame(DiagnosticCheckResult::PASSED, $this->check()->status);
	}

	public function testReportsIncorrectNamespaceWithFilePath(): void {
		$this->fixture->module('Alpha', 'Base3\Alpha', '1.0.0', [], [
			'Service/Example.php' => '<?php namespace Base3\Alpha\Other; final class Example {}'
		]);
		$result = $this->check();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('Alpha/src/Service/Example.php', $result->replacements['sample']);
	}

	public function testReportsLegacyUnprefixedModuleImports(): void {
		$this->fixture->module('Alpha', 'Base3\Alpha');
		$this->fixture->module('Beta', 'Base3\\Beta', '1.0.0', [], [
			'Service/Example.php' => '<?php namespace Base3\\Beta\\Service; use Alpha\\Api\\ITool; final class Example {}'
		]);
		$result = $this->check();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('legacy module reference', $result->replacements['sample']);
	}

	public function testIncludesPluginTestSources(): void {
		$this->fixture->module('Alpha', 'Base3\Alpha');
		$file = $this->fixture->pluginsRoot . '/Alpha/test/Service/ExampleTest.php';
		mkdir(dirname($file), 0777, true);
		file_put_contents($file, '<?php namespace Base3\\Alpha\\Test\\Other; final class ExampleTest {}');
		$result = $this->check();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('expected Base3\\Alpha\\Test\\Service', $result->replacements['sample']);
	}

	public function testReportsOnlyThreeExamplesAndCountsTheRemainder(): void {
		$files = [];
		for($index = 1; $index <= 7; $index++) {
			$name = 'Example' . sprintf('%02d', $index);
			$files['Service/' . $name . '.php'] = '<?php namespace Base3\Alpha\Other; final class ' . $name . ' {}';
		}
		$this->fixture->module('Alpha', 'Base3\Alpha', '1.0.0', [], $files);
		$result = $this->check();
		$this->assertSame(7, $result->details['failed']);
		$this->assertStringContainsString('Example01.php', $result->replacements['sample']);
		$this->assertStringContainsString('Example03.php', $result->replacements['sample']);
		$this->assertStringNotContainsString('Example04.php', $result->replacements['sample']);
		$this->assertStringContainsString('(+4)', $result->replacements['sample']);
		$this->assertLessThanOrEqual(490, strlen($result->replacements['sample']));
	}

}
