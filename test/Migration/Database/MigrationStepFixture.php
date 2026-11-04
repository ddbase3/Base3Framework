<?php declare(strict_types=1);

namespace Base3\Test\Migration\Database;

use Base3\Database\Api\IDatabase;
use Base3\Migration\Api\IDatabaseMigration;

final class MigrationStepFixture implements IDatabaseMigration {

	public int $calls = 0;
	public bool $triggerDatabaseFailure = false;
	public array $recordedVersions = [];

	public function __construct(
		public string $version = '1.0',
		public ?IDatabase $database = null
	) {}

	public static function getName(): string { return 'migrationstepfixture'; }
	public function getVersion(): string { return $this->version; }
	public function getDescription(): string { return 'migration fixture'; }
	public function up(): void {
		$this->calls++;
		$this->recordedVersions[] = $this->version;
		if ($this->triggerDatabaseFailure && $this->database !== null && property_exists($this->database,'error')) {
			$this->database->error = true;
			$this->database->errorText = 'migration operation failed';
		}
	}
}
