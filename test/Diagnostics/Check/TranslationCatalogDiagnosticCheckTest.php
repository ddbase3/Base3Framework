<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Check;

use Base3\Diagnostics\Check\TranslationCatalogDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use PHPUnit\Framework\TestCase;

final class TranslationCatalogDiagnosticCheckTest extends TestCase {

	private const LANGUAGES = ['ar', 'bg', 'de', 'en', 'es', 'fr', 'hi', 'it', 'pl', 'pt', 'ru', 'zh'];

	private DiagnosisSourceFixture $fixture;

	protected function setUp(): void {
		$this->fixture = new DiagnosisSourceFixture();
	}

	protected function tearDown(): void {
		$this->fixture->cleanup();
	}

	private function runCheck(): DiagnosticCheckResult {
		return (new TranslationCatalogDiagnosticCheck(
			$this->fixture->registry(),
			$this->fixture->frameworkRoot,
			$this->fixture->pluginsRoot
		))->run();
	}

	private function writeCatalog(string $root, string $group, string $language, string $contents): void {
		$directory = $root . '/lang/' . $group;
		if(!is_dir($directory)) mkdir($directory, 0777, true);
		file_put_contents($directory . '/' . $language . '.ini', $contents);
	}

	private function completeCatalog(string $root, string $group = 'Settings'): void {
		foreach(self::LANGUAGES as $language) {
			$this->writeCatalog($root, $group, $language, "[example]\nlabel = \"Example\"\ndescription = \"Open {target}\"\n");
		}
	}

	public function testPassesCompleteCatalogAndIndependentSecondGroup(): void {
		$root = $this->fixture->module('Alpha', 'Alpha');
		$this->completeCatalog($root);
		$this->completeCatalog($root, 'Display');
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::PASSED, $result->status);
		$this->assertSame(2, $result->details['inspected']);
	}

	public function testMissingLanguageIsReported(): void {
		$root = $this->fixture->module('Alpha', 'Alpha');
		$this->completeCatalog($root);
		unlink($root . '/lang/Settings/zh.ini');
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('Settings/: missing zh.ini', $result->replacements['sample']);
	}

	public function testMissingAndExtraIndividualKeysAreReportedInEveryLanguage(): void {
		$root = $this->fixture->module('Alpha', 'Alpha');
		$this->completeCatalog($root);
		$this->writeCatalog($root, 'Settings', 'de', "[example]\nlabel = \"Example\"\n");
		$this->writeCatalog($root, 'Settings', 'fr', "[example]\nlabel = \"Example\"\ndescription = \"Open {target}\"\nextra = \"Only fr\"\n");
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('de.ini: missing [example]description', $result->replacements['sample']);
		$this->assertGreaterThanOrEqual(11, $result->details['failed']);
	}

	public function testMismatchedMessagePlaceholdersAreReported(): void {
		$root = $this->fixture->module('Alpha', 'Alpha');
		$this->completeCatalog($root);
		$this->writeCatalog($root, 'Settings', 'es', "[example]\nlabel = \"Example\"\ndescription = \"Open {other}\"\n");
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('es.ini: placeholders differ in [example]description', $result->replacements['sample']);
	}

	public function testEmptyTranslationAndMalformedIniAreFailures(): void {
		$root = $this->fixture->module('Alpha', 'Alpha');
		$this->completeCatalog($root);
		$this->writeCatalog($root, 'Settings', 'en', "[example]\nlabel = \"\"\ndescription = \"Open {target}\"\n");
		$this->writeCatalog($root, 'Settings', 'es', "[example\nlabel = \"bad\"\n");
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('invalid INI syntax', $result->replacements['sample']);
	}

	public function testAdditionalLanguageIsAllowedButMustContainAllKeys(): void {
		$root = $this->fixture->module('Alpha', 'Alpha');
		$this->completeCatalog($root);
		$this->writeCatalog($root, 'Settings', 'nl', "[example]\nlabel = \"Example\"\n");
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::FAILED, $result->status);
		$this->assertStringContainsString('nl.ini: missing [example]description', $result->replacements['sample']);
		$this->writeCatalog($root, 'Settings', 'nl', "[example]\nlabel = \"Example\"\ndescription = \"Open {target}\"\n");
		$this->assertSame(DiagnosticCheckResult::PASSED, $this->runCheck()->status);
	}

	public function testNoCatalogsAreNotApplicable(): void {
		$this->fixture->module('Alpha', 'Alpha');
		$this->assertSame(DiagnosticCheckResult::NOT_APPLICABLE, $this->runCheck()->status);
	}

	public function testMissingManifestIsNotReportedAsSuccess(): void {
		$root = $this->fixture->module('Alpha', 'Alpha');
		$this->completeCatalog($root);
		unlink($root . '/base3.json');
		$this->assertSame(DiagnosticCheckResult::FAILED, $this->runCheck()->status);
	}

}
