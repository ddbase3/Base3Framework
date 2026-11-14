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

namespace Base3\Test\Migration\Database;

use Base3\CoreFoundation\Database\Api\IDatabase;
use Base3\CoreFoundation\Migration\Api\IDatabaseMigration;

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
