<?php declare(strict_types=1);

namespace Base3\Test\Migration\Database;

use Base3\Migration\Api\IDatabaseMigrationProvider;

final class MigrationProviderFixture implements IDatabaseMigrationProvider {

	public function __construct(public array $steps = [], public bool $active = true) {}
	public static function getName(): string { return 'migrationproviderfixture'; }
	public function isActive(): bool { return $this->active; }
	public function getMigrations(): array { return $this->steps; }
}
