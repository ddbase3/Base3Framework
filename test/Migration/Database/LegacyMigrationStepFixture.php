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

use Base3\CoreFoundation\Migration\Api\IDatabaseMigration;
use Base3\CoreFoundation\Migration\Api\ILegacyDatabaseMigration;

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
