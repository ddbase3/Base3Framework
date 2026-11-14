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

use Base3\CoreFoundation\Diagnostics\Inspection\PhpSourceInspector;
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
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; use Base3\CoreFoundation\Migration\Api\IDatabaseMigration as M; use Base3\CoreFoundation\Migration\Api\ILegacyDatabaseMigration as L; final class AlphaMigration001 implements M, L { public function getVersion(): string { return "001"; } public function up(): void {} /* public function down(): void {} */ public function down(): void { throw new \Base3\CoreFoundation\Migration\Exception\MigrationException("Not implemented"); } }'
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
			'Migration/AlphaMigration001.php' => '<?php namespace Alpha\Migration; class AlphaMigration001 implements \Base3\CoreFoundation\Migration\Api\IDatabaseMigration { public function getVersion(): string { return "001"; } public function up(): void { function down(): void {} } }'
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
