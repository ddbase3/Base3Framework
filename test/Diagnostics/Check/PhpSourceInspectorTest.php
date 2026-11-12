<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics\Check;

use Base3\Diagnostics\Inspection\PhpSourceInspector;
use PHPUnit\Framework\TestCase;

final class PhpSourceInspectorTest extends TestCase {

	private DiagnosisSourceFixture $fixture;

	protected function setUp(): void {
		$this->fixture = new DiagnosisSourceFixture();
	}

	protected function tearDown(): void {
		$this->fixture->cleanup();
	}

	public function testLegacyMarkerAndDownSignatureUseActualClassTokens(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; use Base3\Migration\Api\IDatabaseMigration as M; use Base3\Migration\Api\ILegacyDatabaseMigration as L; final class AlphaMigration001 implements M, L { public function getVersion(): string { return "001"; } public function up(): void {} /* public function down(): void {} */ public function down(): void { throw new \Base3\Migration\Exception\MigrationException("Not implemented"); } }'
		]);
		$source = (new PhpSourceInspector())->inspect($this->fixture->pluginsRoot . '/Alpha/src/Migration/AlphaMigration001.php');
		$this->assertTrue($source['migration']);
		$this->assertTrue($source['migrationCertain']);
		$this->assertTrue($source['legacyMarker']);
		$this->assertTrue($source['downDeclared']);
		$this->assertTrue($source['downSignature']);
	}

	public function testNestedDownFunctionDoesNotSatisfyClassMethod(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; class AlphaMigration001 implements \Base3\Migration\Api\IDatabaseMigration { public function getVersion(): string { return "001"; } public function up(): void { function down(): void {} } }'
		]);
		$source = (new PhpSourceInspector())->inspect($this->fixture->pluginsRoot . '/Alpha/src/Migration/AlphaMigration001.php');
		$this->assertFalse($source['downDeclared']);
	}

	public function testInvalidPhpIsReportedWithoutLoadingCode(): void {
		$this->fixture->module('Alpha', 'Alpha', '1.0.0', [], [
			'Service/Broken.php' => '<?php namespace Alpha\\Service; class Broken { invalid php syntax }'
		]);
		$source = (new PhpSourceInspector())->inspect($this->fixture->pluginsRoot . '/Alpha/src/Service/Broken.php');
		$this->assertSame('Invalid PHP syntax', $source['error']);
	}

}
