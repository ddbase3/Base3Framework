<?php declare(strict_types=1);

namespace Base3\Test\Migration\Database;

use Base3\Migration\Api\IDatabaseMigration;
use Base3\Migration\Api\ILegacyDatabaseMigration;

/** Applied steps with this marker remain discoverable but do not validate file checksums. */
final class LegacyMigrationStepFixture implements IDatabaseMigration, ILegacyDatabaseMigration {

	public int $calls = 0;

	public static function getName(): string {
		return 'legacymigrationstepfixture';
	}

	public function getVersion(): string {
		return '001';
	}

	public function getDescription(): string {
		return 'Legacy step fixture';
	}

	public function up(): void {
		$this->calls++;
	}

}
