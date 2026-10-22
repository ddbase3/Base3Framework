<?php declare(strict_types=1);

namespace Base3\Test\Diagnostics;

use Base3\Diagnostics\Feature\DiagnosticReportsFeature;
use Base3\Translation\Api\ITranslation;
use PHPUnit\Framework\TestCase;

final class DiagnosticReportsFeatureTest extends TestCase {

	public function testPermissionAndDefaultAudience(): void {
		$translation = $this->createMock(ITranslation::class);
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
