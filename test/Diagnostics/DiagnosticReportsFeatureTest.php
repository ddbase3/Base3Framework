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

namespace Base3\Test\Diagnostics;

use Base3\Core\Diagnostics\Feature\DiagnosticReportsFeature;
use Base3\CoreFoundation\Translation\Api\ITranslation;
use PHPUnit\Framework\TestCase;

final class DiagnosticReportsFeatureTest extends TestCase {

	public function testPermissionAndDefaultAudience(): void {
		$translation = $this->createStub(ITranslation::class);
		$translation->method('translate')->willReturnCallback(
			static fn(string $set, string $section, string $key): string => $section . '.' . $key
		);
		$feature = new DiagnosticReportsFeature($translation);
		self::assertSame('diagnosticreportsfeature', $feature::getName());
		self::assertSame('diagnosticreportsfeature.label', $feature->getLabel());
		self::assertSame('diagnosticreportsfeature.description', $feature->getDescription());
		$permissions = $feature->getPermissions();
		self::assertCount(1, $permissions);
		self::assertSame('feature', $permissions[0]->scope);
		self::assertSame($feature::getName(), $permissions[0]->permission);
		self::assertSame(['group' => 'development'], $feature->getDefaultAudience());
	}
}
