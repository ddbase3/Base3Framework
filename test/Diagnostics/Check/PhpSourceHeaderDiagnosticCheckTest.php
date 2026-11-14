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

use Base3\Core\Diagnostics\Check\PhpSourceHeaderDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
use PHPUnit\Framework\TestCase;

final class PhpSourceHeaderDiagnosticCheckTest extends TestCase {

	private DiagnosisSourceFixture $fixture;

	protected function setUp(): void {
		$this->fixture = new DiagnosisSourceFixture();
	}

	protected function tearDown(): void {
		$this->fixture->cleanup();
	}

	private function check(): DiagnosticCheckResult {
		return (new PhpSourceHeaderDiagnosticCheck(
			$this->fixture->registry(),
			$this->fixture->frameworkRoot,
			$this->fixture->pluginsRoot,
			$this->fixture->root . '/unused-tests'
		))->run();
	}

	private function source(string $module, string $description): string {
		return "<?php declare(strict_types=1);\n\n"
			. "/***********************************************************************\n"
			. " * This file is part of " . $module . " for BASE3 Framework.\n"
			. " *\n"
			. " * " . $description . "\n"
			. " *\n"
			. " * Developed by Daniel Dahme\n"
			. " * Licensed under GPL-3.0\n"
			. " * https://www.gnu.org/licenses/gpl-3.0.en.html\n"
			. " **********************************************************************/\n\n"
			. "namespace Base3\\" . $module . "\\Service;\n"
			. "final class Example { }\n";
	}

	public function testAcceptsPluginSpecificHeader(): void {
		$this->fixture->module('Alpha', 'Base3\\Alpha', '1.0.0', [], [
			'Service/Example.php' => $this->source('Alpha', 'Alpha provides shared application services for projects and integrations everywhere.')
		]);
		$this->assertSame(DiagnosticCheckResult::PASSED, $this->check()->status);
	}

	public function testRejectsHeaderBorrowedFromAnotherPlugin(): void {
		$this->fixture->module('Alpha', 'Base3\\Alpha', '1.0.0', [], [
			'Service/Example.php' => $this->source('Beta', 'Beta provides a separate set of application services.')
		]);
		$result = $this->check();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('does not belong to Alpha', $result->replacements['sample']);
	}

	public function testRejectsMissingHeader(): void {
		$this->fixture->module('Alpha', 'Base3\\Alpha', '1.0.0', [], [
			'Service/Example.php' => '<?php namespace Base3\\Alpha\\Service; final class Example {}'
		]);
		$result = $this->check();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('missing BASE3 file header', $result->replacements['sample']);
	}

	public function testRejectsIncompleteHeader(): void {
		$this->fixture->module('Alpha', 'Base3\\Alpha', '1.0.0', [], [
			'Service/Example.php' => str_replace('Licensed under GPL-3.0', 'Copyright only', $this->source('Alpha', 'Alpha provides shared application services for projects and integrations everywhere.'))
		]);
		$this->assertSame(DiagnosticCheckResult::FAILED, $this->check()->status);
	}

	public function testIncludesTestFilesOutsideSourceTree(): void {
		$this->fixture->module('Alpha', 'Base3\\Alpha');
		$file = $this->fixture->pluginsRoot . '/Alpha/test/Service/ExampleTest.php';
		mkdir(dirname($file), 0777, true);
		file_put_contents($file, '<?php namespace Base3\\Alpha\\Test\\Service; final class ExampleTest {}');
		$result = $this->check();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('Alpha/test/Service/ExampleTest.php', $result->replacements['sample']);
	}

	public function testDoesNotRequireBase3HeaderFromExternalNamespaces(): void {
		$this->fixture->module('Alpha', 'Base3\\Alpha', '1.0.0', [], [
			'Service/External.php' => '<?php namespace Vendor\\Library; final class External {}'
		]);
		$this->assertSame(DiagnosticCheckResult::PASSED, $this->check()->status);
	}

	public function testListsThreeHeaderFailuresAndTheRemainingCount(): void {
		$files = [];
		for($index = 1; $index <= 6; $index++) {
			$name = 'Example' . sprintf('%02d', $index);
			$files['Service/' . $name . '.php'] = '<?php namespace Base3\Alpha\Service; final class ' . $name . ' {}';
		}
		$this->fixture->module('Alpha', 'Base3\Alpha', '1.0.0', [], $files);
		$result = $this->check();
		$this->assertSame(6, $result->details['failed']);
		$this->assertStringContainsString('Example01.php: missing BASE3 file header', $result->replacements['sample']);
		$this->assertStringContainsString('Example03.php', $result->replacements['sample']);
		$this->assertStringNotContainsString('Example04.php', $result->replacements['sample']);
		$this->assertStringContainsString('(+3)', $result->replacements['sample']);
		$this->assertLessThanOrEqual(490, strlen($result->replacements['sample']));
	}

}
