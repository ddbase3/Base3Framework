<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Check;

use Base3\Diagnostics\Check\LegacyMigrationDiagnosticCheck;
use Base3\Diagnostics\Dto\DiagnosticCheckResult;
use PHPUnit\Framework\TestCase;

final class LegacyMigrationDiagnosticCheckTest extends TestCase {

	private DiagnosisSourceFixture $fixture;

	protected function setUp(): void {
		$this->fixture = new DiagnosisSourceFixture();
	}

	protected function tearDown(): void {
		$this->fixture->cleanup();
	}

	private function runCheck(): DiagnosticCheckResult {
		return (new LegacyMigrationDiagnosticCheck(
			$this->fixture->registry(),
			$this->fixture->frameworkRoot,
			$this->fixture->pluginsRoot
		))->run();
	}

	public function testCountsMarkersAsOneAggregatedFinding(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; use Base3\Migration\Api\IDatabaseMigration; use Base3\Migration\Api\ILegacyDatabaseMigration; final class AlphaMigration001 implements IDatabaseMigration, ILegacyDatabaseMigration { public function getVersion(): string { return "001"; } public function up(): void {} public function down(): void {} }',
			'Migration/AlphaMigration002.php' => '<?php namespace Alpha\Migration; use Base3\Migration\Api\IDatabaseMigration; use Base3\Migration\Api\ILegacyDatabaseMigration; final class AlphaMigration002 implements IDatabaseMigration, ILegacyDatabaseMigration { public function getVersion(): string { return "002"; } public function up(): void {} public function down(): void {} }'
		]);
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::WARNING, $result->status);
		$this->assertSame(2, $result->details['inspected']);
		$this->assertSame(2, $result->details['legacy']);
		$this->assertSame(0, $result->details['unverifiable']);
		$this->assertStringContainsString('AlphaMigration001.php', $result->replacements['sample']);
	}

	public function testReportsGreenWhenNoLegacyMarkersRemain(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; use Base3\Migration\Api\IDatabaseMigration; final class AlphaMigration001 implements IDatabaseMigration { public function getVersion(): string { return "001"; } public function up(): void {} public function down(): void {} }'
		]);
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::PASSED, $result->status);
		$this->assertSame(0, $result->details['legacy']);
	}

	public function testSyntaxErrorIsNotSilentlyIgnored(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; class AlphaMigration001 { invalid php }'
		]);
		$this->assertSame(DiagnosticCheckResult::FAILED, $this->runCheck()->status);
	}

	public function testUnknownMigrationIsNotDeclaredLegacyFree(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration002.php' => '<?php namespace Alpha\Migration; final class AlphaMigration002 extends UnknownMigrationBase {}'
		]);
		$result = $this->runCheck();
		$this->assertSame(DiagnosticCheckResult::WARNING, $result->status);
		$this->assertSame(1, $result->details['unverifiable']);
	}

	public function testNoMigrationsAreNotApplicable(): void {
		$this->fixture->module('Alpha', 'Alpha');
		$this->assertSame(DiagnosticCheckResult::NOT_APPLICABLE, $this->runCheck()->status);
	}

}
