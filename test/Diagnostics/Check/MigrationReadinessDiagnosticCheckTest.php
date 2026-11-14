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

use Base3\Core\Diagnostics\Check\MigrationReadinessDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
use PHPUnit\Framework\TestCase;

final class MigrationReadinessDiagnosticCheckTest extends TestCase {

	private DiagnosisSourceFixture $fixture;

	protected function setUp(): void {
		$this->fixture = new DiagnosisSourceFixture();
	}

	protected function tearDown(): void {
		$this->fixture->cleanup();
	}

	private function runCheck(): DiagnosticCheckResult {
		return (new MigrationReadinessDiagnosticCheck(
			$this->fixture->registry(),
			$this->fixture->frameworkRoot,
			$this->fixture->pluginsRoot
		))->run();
	}

	public function testMissingDownProducesStagedWarningWithoutFailure(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; use Base3\CoreFoundation\Migration\Api\IDatabaseMigration; final class AlphaMigration001 implements IDatabaseMigration { public function getVersion(): string { return "001"; } public function up(): void {} }'
		]);
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::WARNING, $result->status);
		$this->assertSame(1, $result->details['inspected']);
		$this->assertStringContainsString('down() is not yet declared', $result->replacements['sample']);
	}

	public function testLegacyMarkerDoesNotAffectDownReadiness(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; use Base3\CoreFoundation\Migration\Api\IDatabaseMigration as MigrationContract; use Base3\CoreFoundation\Migration\Api\ILegacyDatabaseMigration as Legacy; use Base3\CoreFoundation\Migration\Exception\MigrationException; final class AlphaMigration001 implements MigrationContract, Legacy { public function up(): void {} public function getVersion(): string { return "001"; } public function down(): void { throw new MigrationException("Not implemented"); } }'
		]);
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::PASSED, $result->status);
		$this->assertSame(0, $result->details['warning']);
	}

	public function testExplicitNotImplementedExceptionIsNotReportedAsDiagnosticFinding(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration003.php' => '<?php namespace Alpha\Migration; use Base3\CoreFoundation\Migration\Api\IDatabaseMigration; use Base3\CoreFoundation\Migration\Exception\MigrationException; final class AlphaMigration003 implements IDatabaseMigration { public function up(): void {} public function getVersion(): string { return "003"; } public function down(): void { throw new MigrationException("Not implemented"); } }'
		]);
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::PASSED, $result->status);
		$this->assertSame(0, $result->details['warning']);
	}

	public function testValidNonLegacyDownSignatureIsStructurallyAccepted(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; final class AlphaMigration001 implements \Base3\CoreFoundation\Migration\Api\IDatabaseMigration { public function up(): void {} public function getVersion(): string { return "001"; } public function down(): void { $this->up(); } }'
		]);
		$this->assertSame(DiagnosticCheckResult::PASSED, $this->runCheck()->status);
	}

	public function testInvalidDownSignatureIsReportedWithoutLoadingSource(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; final class AlphaMigration001 implements \Base3\CoreFoundation\Migration\Api\IDatabaseMigration { public function up(): void {} public function getVersion(): string { return "001"; } private static function down(string $arg): int { return 1; } }'
		]);
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::WARNING, $result->status);
		$this->assertStringContainsString('down() must be public', $result->replacements['sample']);
	}

	public function testEmptyDownIsAcceptedAsStructurallyCorrect(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; final class AlphaMigration001 implements \Base3\CoreFoundation\Migration\Api\IDatabaseMigration { public function up(): void {} public function getVersion(): string { return "001"; } public function down(): void { /* empty */ } }'
		]);
		$this->assertSame(DiagnosticCheckResult::PASSED, $this->runCheck()->status);
	}

	public function testDownMethodOnlyInCommentDoesNotCount(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; final class AlphaMigration001 implements \Base3\CoreFoundation\Migration\Api\IDatabaseMigration { public function up(): void {} public function getVersion(): string { return "001"; } /* public function down(): void {} */ }'
		]);
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::WARNING, $result->status);
		$this->assertStringContainsString('not yet declared', $result->replacements['sample']);
	}

	public function testUnrelatedCodeIsNotConsideredAMigration(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigrationProvider.php' => '<?php namespace Alpha\Migration; final class AlphaMigrationProvider { public function getMigrations(): array { return []; } }',
			'Service/Service.php' => '<?php namespace Alpha\Service; final class Service { public function up(): void {} }'
		]);
		$this->assertSame(DiagnosticCheckResult::NOT_APPLICABLE, $this->runCheck()->status);
	}

	public function testUnverifiableInheritedMigrationIsNotReportedAsGreen(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration002.php' => '<?php namespace Alpha\Migration; final class AlphaMigration002 extends UnknownMigrationBase {}'
		]);
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::WARNING, $result->status);
		$this->assertStringContainsString('cannot be verified statically', $result->replacements['sample']);
	}

	public function testMalformedSourcesAreReportedWithoutExecution(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; class AlphaMigration001 { malformed php code }'
		]);
		$this->assertSame(DiagnosticCheckResult::FAILED, $this->runCheck()->status);
	}

	public function testMissingManifestDoesNotProduceFalsePass(): void {
		$this->fixture->module('Alpha', 'Alpha');
		unlink($this->fixture->pluginsRoot . '/Alpha/base3.json');
		$this->assertSame(DiagnosticCheckResult::FAILED, $this->runCheck()->status);
	}

	public function testNoRelevantMigrationReturnsNotApplicable(): void {
		$this->fixture->module('Alpha', 'Alpha');
		$this->assertSame(DiagnosticCheckResult::NOT_APPLICABLE, $this->runCheck()->status);
	}

}
