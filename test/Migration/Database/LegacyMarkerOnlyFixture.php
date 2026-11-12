<?php declare(strict_types=1);

namespace Base3\Test\Migration\Database;

use Base3\Migration\Api\ILegacyDatabaseMigration;

/** A marker alone is not a valid database migration. */
final class LegacyMarkerOnlyFixture implements ILegacyDatabaseMigration {
}
