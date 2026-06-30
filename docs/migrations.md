# BASE3 Framework Database Migrations

## Purpose

This document explains how database migrations work in the BASE3 framework.

It is written for framework developers, plugin authors, and project-plugin authors who need to understand:

* why migrations exist in BASE3
* why the default runner does nothing
* how a project enables database migrations
* how framework and plugin code provide migration steps
* how migration providers are discovered
* how provider activation depends on the final runtime composition
* how migration state and locks are stored
* how private backend tables differ from domain tables
* how to write safe, immutable migration steps
* where migrations are executed in the bootstrap lifecycle

The goal is practical understanding. After reading this document, a developer should be able to add a migration provider, add migration steps, wire the database-backed runner in a project plugin, and reason about when a migration should or should not run.

---

## 1. Main idea

BASE3 can run with or without a database.

Some projects use only file-based configuration, file-based state, or host-system services. Other projects wire `IDatabase`, database-backed settings, database-backed state, database-backed configuration, domain repositories, queues, logs, and plugin-specific tables.

Because of that, the framework must not assume that a database exists.

The migration system therefore has this core rule:

```text
The framework provides the migration slot.
The default runner is a no-op runner.
The project plugin decides whether database migrations are active.
```

In other words:

```text
Core bootstrap:
  IMigrationRunner -> NoMigrationRunner

Project plugin with database:
  IDatabase -> concrete database implementation
  IMigrationRunner -> DatabaseMigrationRunner
```

The bootstrap may always call:

```php
$container->get(IMigrationRunner::class)->migrate();
```

If the project did not enable migrations, this call is harmless because the default runner does nothing.

---

## 2. Why this design exists

A simple automatic migration system sounds attractive, but BASE3 has an important composition model:

```text
Final implementations are selected by the project plugin or custom bootstrap.
```

For example, `IConfiguration` may be wired to a file-based implementation in one project and a database-backed implementation in another project.

```text
Project A:
  IConfiguration -> ConfigFile
  No database table required.

Project B:
  IConfiguration -> DatabaseConfiguration
  Database table required.
```

The same applies to `IStateStore`, `ISettingsStore`, logging, queues, plugin repositories, and other services.

This means migrations must not simply be attached to plugin presence alone. They must be attached to the active runtime composition.

A migration provider owns a schema area, but it should only be active when that schema area is relevant for the current project.

---

## 3. Migration roles

The migration subsystem has four main roles.

```text
IMigrationRunner
  Executes migrations, or intentionally does nothing.

IDatabaseMigrationProvider
  Provides migration steps for one schema owner.

IDatabaseMigration
  Describes and applies one immutable migration step.

DatabaseMigrationRepository
  Stores which migration steps have already been applied.
```

The database-backed runner also uses a lock:

```text
DatabaseMigrationLock
  Prevents concurrent migration execution.
```

---

## 4. Directory structure

The framework migration subsystem lives under:

```text
src/Migration/
├── Api/
│   ├── IDatabaseMigration.php
│   ├── IDatabaseMigrationProvider.php
│   └── IMigrationRunner.php
├── Database/
│   ├── DatabaseMigrationLock.php
│   ├── DatabaseMigrationRepository.php
│   └── DatabaseMigrationRunner.php
├── Exception/
│   └── MigrationException.php
└── No/
    └── NoMigrationRunner.php
```

A plugin can add its own migration classes under its own `src/` tree, for example:

```text
plugin/ExamplePlugin/
└── src/
    ├── Migration/
    │   ├── ExamplePluginMigrationProvider.php
    │   ├── Migration001CreateTables.php
    │   ├── Migration002AddIndexes.php
    │   └── Migration003BackfillDefaults.php
    └── ExamplePlugin.php
```

Only classes below `src/` are discovered by the default plugin class map.

---

## 5. Bootstrap integration

The default bootstrap registers the no-op runner.

```php
use Base3\Migration\Api\IMigrationRunner;
use Base3\Migration\No\NoMigrationRunner;

$container
    ->set(IMigrationRunner::class, fn() => new NoMigrationRunner(), IContainer::SHARED);
```

The runner is called after plugin initialization and after `bootstrap.start`, but before request execution.

```php
// plugins
$plugins = $container->get(IClassMap::class)->getInstancesByInterface(IPlugin::class);
foreach ($plugins as $plugin) $plugin->init();
$hookManager->dispatch('bootstrap.start');

// migrations
$container->get(IMigrationRunner::class)->migrate();
$hookManager->dispatch('bootstrap.migrated');

// go
$serviceSelector = $container->get(IServiceSelector::class);
echo $serviceSelector->go();
$hookManager->dispatch('bootstrap.finish');
```

This point in the lifecycle is important:

* all plugins have had a chance to register or replace services
* the project plugin has had a chance to wire the final `IDatabase`
* the project plugin has had a chance to replace `NoMigrationRunner`
* the request has not yet reached the service selector

This gives migrations a stable composition state.

---

## 6. Default behavior: no migrations

`NoMigrationRunner` is the framework default.

```php
<?php declare(strict_types=1);

namespace Base3\Migration\No;

use Base3\Migration\Api\IMigrationRunner;

final class NoMigrationRunner implements IMigrationRunner {

    public function migrate(): void {
    }
}
```

This is intentional.

BASE3 must be able to run in projects without a database. The default bootstrap should not force `IDatabase` to exist and should not create database tables automatically.

---

## 7. Enabling database migrations in a project plugin

A project plugin that uses a database should wire `IDatabase` and replace the migration runner.

Conceptual example:

```php
<?php declare(strict_types=1);

namespace ProjectPlugin;

use Base3\Api\IClassMap;
use Base3\Api\IContainer;
use Base3\Api\IPlugin;
use Base3\Database\Api\IDatabase;
use Base3\Database\Mysql\MysqlDatabase;
use Base3\Migration\Api\IMigrationRunner;
use Base3\Migration\Database\DatabaseMigrationRunner;

final class ProjectPlugin implements IPlugin {

    public function __construct(
        private readonly IContainer $container
    ) {}

    public static function getName(): string {
        return 'projectplugin';
    }

    public function init() {
        $this->container
            ->set(
                IDatabase::class,
                fn($c) => new MysqlDatabase($c->get(IConfiguration::class)),
                IContainer::SHARED
            )
            ->set(
                IMigrationRunner::class,
                fn($c) => new DatabaseMigrationRunner(
                    $c->get(IClassMap::class),
                    $c->get(IDatabase::class)
                ),
                IContainer::SHARED
            );
    }
}
```

The exact database construction depends on the active `IDatabase` implementation in the project.

Do not use `NOOVERWRITE` in the project plugin when the project is making a final decision. The project plugin is the composition layer and should deliberately replace the default no-op runner.

---

## 8. Core interfaces

## 8.1 `IMigrationRunner`

```php
<?php declare(strict_types=1);

namespace Base3\Migration\Api;

interface IMigrationRunner {

    public function migrate(): void;
}
```

The runner is deliberately small.

It answers one question:

```text
Bring the current runtime composition to the expected migration state.
```

A runner may be:

* a no-op runner
* a database-backed runner
* a CLI-only runner
* a project-specific runner
* a runner that checks a policy before doing work

---

## 8.2 `IDatabaseMigrationProvider`

```php
<?php declare(strict_types=1);

namespace Base3\Migration\Api;

use Base3\Api\IBase;

interface IDatabaseMigrationProvider extends IBase {

    public function isActive(): bool;

    /**
     * @return array<int, IDatabaseMigration|string>
     */
    public function getMigrations(): array;
}
```

A provider owns one schema area.

Examples:

```text
DatabaseStateStoreMigrationProvider
DatabaseSettingsStoreMigrationProvider
DatabaseConfigurationMigrationProvider
ExamplePluginMigrationProvider
AssistantRunMigrationProvider
MessagingQueueMigrationProvider
```

The provider decides whether its migrations are relevant for the current composition.

For example:

```text
DatabaseStateStoreMigrationProvider
  active only when IStateStore is wired to DatabaseStateStore

DatabaseConfigurationMigrationProvider
  active only when IConfiguration is wired to DatabaseConfiguration

ExamplePluginMigrationProvider
  active only when the plugin's database feature is enabled
```

This keeps schema ownership with the code that owns the data structure, while still allowing the project plugin to decide which implementations are active.

---

## 8.3 `IDatabaseMigration`

```php
<?php declare(strict_types=1);

namespace Base3\Migration\Api;

use Base3\Api\IBase;

interface IDatabaseMigration extends IBase {

    public function getVersion(): string;

    public function getDescription(): string;

    public function up(): void;
}
```

A migration is one immutable step.

It should do one clear thing:

```text
001_create_tables
002_add_indexes
003_add_expires_at
004_backfill_defaults
005_change_value_column_to_mediumtext
```

A migration is not a general installer and should not contain unrelated setup logic.

---

## 9. Migration provider example

This example shows a provider for a database-backed state store.

```php
<?php declare(strict_types=1);

namespace Base3\State\Database\Migration;

use Base3\Api\IContainer;
use Base3\Migration\Api\IDatabaseMigrationProvider;
use Base3\State\Api\IStateStore;
use Base3\State\Database\DatabaseStateStore;

final class DatabaseStateStoreMigrationProvider implements IDatabaseMigrationProvider {

    public function __construct(
        private readonly IContainer $container
    ) {}

    public static function getName(): string {
        return 'databasestatestoremigrationprovider';
    }

    public function isActive(): bool {
        if (!$this->container->has(IStateStore::class)) {
            return false;
        }

        return $this->container->get(IStateStore::class) instanceof DatabaseStateStore;
    }

    public function getMigrations(): array {
        return [
            Migration001CreateStateStoreTable::class,
            Migration002AddUpdatedAtColumn::class,
            Migration003AddExpiresAtColumn::class,
            Migration004AddExpiresAtIndex::class,
        ];
    }
}
```

This provider depends only on `IContainer` so that it can be instantiated even when the related service is not present.

That is useful for provider discovery. The provider itself decides whether it is active.

---

## 10. Migration step example

A migration step should receive its dependencies through constructor injection.

```php
<?php declare(strict_types=1);

namespace Base3\State\Database\Migration;

use Base3\Database\Api\IDatabase;
use Base3\Migration\Api\IDatabaseMigration;

final class Migration004AddExpiresAtIndex implements IDatabaseMigration {

    public function __construct(
        private readonly IDatabase $database
    ) {}

    public static function getName(): string {
        return 'databasestore_004_add_expires_at_index';
    }

    public function getVersion(): string {
        return '004';
    }

    public function getDescription(): string {
        return 'Adds an index for expires_at in the database state store table.';
    }

    public function up(): void {
        $this->database->connect();

        $this->database->nonQuery(
            "ALTER TABLE `base3_statestore`
             ADD INDEX `idx_expires_at` (`expires_at`)"
        );
    }
}
```

The runner checks `IDatabase::isError()` after the migration has run and stores the migration state only after successful execution.

---

## 11. Returning instances versus class names

A provider may return migration instances:

```php
public function getMigrations(): array {
    return [
        new Migration001CreateTables($this->database),
        new Migration002AddIndexes($this->database),
    ];
}
```

Or it may return class names:

```php
public function getMigrations(): array {
    return [
        Migration001CreateTables::class,
        Migration002AddIndexes::class,
    ];
}
```

Class names are usually cleaner in BASE3 because the runner can instantiate them through the class map and normal constructor injection can be used.

Use instances only when the provider has a specific reason to construct migration objects itself.

---

## 12. Ordering

The database runner sorts providers by provider `getName()`.

Inside each provider, the runner sorts migrations by:

```text
1. getVersion() using natural string comparison
2. getName() as tie breaker
```

Recommended version format:

```text
001
002
003
004
```

or:

```text
2026063001
2026063002
```

Do not rely on the array order returned by `getMigrations()`. The runner sorts migrations before execution.

---

## 13. Applied migration state

The database runner stores applied migrations in:

```text
base3_migrations
```

Conceptual schema:

```sql
CREATE TABLE IF NOT EXISTS `base3_migrations` (
    `provider` VARCHAR(190) NOT NULL,
    `migration` VARCHAR(190) NOT NULL,
    `version` VARCHAR(64) NOT NULL,
    `checksum` CHAR(64) NOT NULL,
    `applied_at` DATETIME NOT NULL,
    `execution_ms` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`provider`, `migration`),
    KEY `idx_provider_version` (`provider`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

The primary identity is:

```text
provider + migration
```

This lets different schema owners use independent migration names without colliding.

---

## 14. Migration locks

The database runner uses a database lock table:

```text
base3_migration_locks
```

Conceptual schema:

```sql
CREATE TABLE IF NOT EXISTS `base3_migration_locks` (
    `name` VARCHAR(190) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `acquired_at` INT NOT NULL,
    `expires_at` INT NOT NULL,
    PRIMARY KEY (`name`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

The lock prevents two parallel requests or workers from running migrations at the same time.

Locks have a TTL. This matters because a crashed process must not block migration execution forever.

---

## 15. Checksums and immutability

The runner stores a checksum for each applied migration.

When a migration has already been applied, the runner calculates the current checksum and compares it with the stored checksum.

If the checksum differs, the runner throws a `MigrationException`.

This protects an important rule:

```text
Do not edit old migration files after they have been released.
```

If a released migration was incomplete, do not change it. Add a new migration.

Good:

```text
001_create_tables
002_add_missing_index
```

Bad:

```text
001_create_tables  edited after release to also add the missing index
```

Migration steps are historical records. Treat them as immutable.

---

## 16. What belongs in a migration

Use migrations for database structure or data changes that must happen once.

Good examples:

```text
create domain tables
add columns
add indexes
change column type
backfill data
split one table into two tables
rename technical fields
create default rows required by a storage backend
```

Bad examples:

```text
run an import
call an external API
clear a cache
send emails
process user requests
perform recurring cleanup
register services
write normal runtime state
```

Use jobs, services, events, hooks, or runtime logic for those tasks instead.

---

## 17. Private backend tables versus domain tables

Not every table needs a full migration from the beginning.

BASE3 distinguishes between:

```text
Private technical backend tables
Domain or plugin data tables
Versioned schema evolution
```

## 17.1 Private technical backend tables

Examples:

```text
DatabaseStateStore table
DatabaseSettingsStore table
DatabaseConfiguration table
DatabaseMigrationRepository table
DatabaseMigrationLock table
```

For a private technical backend table, a small internal `ensureTable()` method is acceptable.

Reason:

```text
The table is an implementation detail of the concrete backend.
```

If the backend is not wired, the table is irrelevant.

The project plugin should not need to know how the backend persists its private data.

## 17.2 Domain or plugin data tables

Examples:

```text
crm_contacts
crm_tasks
assistant_runs
message_queue
resource_metadata
media_objects
```

These should normally use real migration steps.

The service hot path should not repeatedly inspect and repair domain schema.

## 17.3 Versioned schema evolution

Even for private backend tables, once evolution becomes more than simple table creation, use migration steps.

Example:

```text
001_create_state_store_table
002_add_updated_at_column
003_add_expires_at_column
004_add_expires_at_index
005_change_value_to_mediumtext
```

This gives old installations a clear upgrade path.

---

## 18. `ensureTable()` and migrations together

A database-backed technical service may still keep a small `ensureTable()` method.

Typical pattern:

```php
private function ensureReady(): void {
    $this->database->connect();

    if ($this->initialized) {
        return;
    }

    $this->ensureTable();
    $this->initialized = true;
}
```

This is useful when the service owns a private minimal table and needs to work on a fresh installation.

However, do not keep adding complex schema logic into `ensureTable()` forever.

Use this split:

```text
ensureTable()
  creates the minimum table needed by the backend

Migration steps
  evolve existing installations version by version
```

---

## 19. Configuration example

Configuration demonstrates why migrations must depend on composition.

```text
IConfiguration -> ConfigFile
  no database migration needed

IConfiguration -> DatabaseConfiguration
  database table needed
```

The project plugin should only wire the final implementation.

It should not manually reproduce the table setup of `DatabaseConfiguration`.

Correct ownership:

```text
DatabaseConfiguration
  owns its private base table behavior

DatabaseConfigurationMigrationProvider
  owns versioned schema changes for that backend

Project plugin
  chooses whether DatabaseConfiguration is active
```

This keeps table ownership with the implementation that actually uses the table.

---

## 20. State store example

The state store is a typical technical backend case.

```text
IStateStore -> file or memory implementation
  no database state table needed

IStateStore -> DatabaseStateStore
  database state table needed
```

A database-backed state store may use internal `ensureTable()` for its base table.

Later schema changes can be added through `DatabaseStateStoreMigrationProvider`.

Example migration history:

```text
001_create_state_store_table
002_add_updated_at_column
003_add_expires_at_column
004_add_expires_at_index
005_change_value_column_to_mediumtext
```

The provider should be active only when the database-backed state store is wired.

---

## 21. Plugin domain example

A plugin that owns domain tables should provide a migration provider.

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Migration;

use Base3\Migration\Api\IDatabaseMigrationProvider;

final class ExamplePluginMigrationProvider implements IDatabaseMigrationProvider {

    public static function getName(): string {
        return 'examplepluginmigrationprovider';
    }

    public function isActive(): bool {
        return true;
    }

    public function getMigrations(): array {
        return [
            Migration001CreateExampleTables::class,
            Migration002AddExampleIndexes::class,
            Migration003BackfillExampleDefaults::class,
        ];
    }
}
```

If the plugin has optional database features, `isActive()` should reflect that.

For example, it may check settings, configuration, or whether a project-specific service is wired.

---

## 22. Plugin migration step example

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Migration;

use Base3\Database\Api\IDatabase;
use Base3\Migration\Api\IDatabaseMigration;

final class Migration001CreateExampleTables implements IDatabaseMigration {

    public function __construct(
        private readonly IDatabase $database
    ) {}

    public static function getName(): string {
        return 'exampleplugin_001_create_example_tables';
    }

    public function getVersion(): string {
        return '001';
    }

    public function getDescription(): string {
        return 'Creates initial ExamplePlugin database tables.';
    }

    public function up(): void {
        $this->database->connect();

        $this->database->nonQuery(
            "CREATE TABLE IF NOT EXISTS `example_item` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}
```

Use `CREATE TABLE IF NOT EXISTS` for initial table creation when appropriate.

For later schema changes, prefer explicit `ALTER TABLE` migration steps.

---

## 23. Error handling

If a migration fails, the runner throws `MigrationException`.

The migration is not marked as applied.

On the next run, the same migration will be attempted again unless the failure left partial schema changes behind.

Because many DDL statements auto-commit in MySQL and MariaDB, migration authors should design steps carefully.

Recommended practices:

```text
keep each step small
avoid mixing many unrelated changes in one migration
check manually after production failures
prefer additive changes when possible
avoid destructive changes unless clearly required
```

---

## 24. Idempotency

Migrations are normally executed exactly once by the runner.

However, migration SQL should still be reasonably safe when possible.

Good:

```sql
CREATE TABLE IF NOT EXISTS `example_item` (...)
```

Potentially unsafe if rerun after a partial failure:

```sql
ALTER TABLE `example_item` ADD COLUMN `status` VARCHAR(32) NOT NULL
```

For non-idempotent operations, keep the step narrow and test it against the expected previous schema.

Avoid turning every migration into a large schema-inspection script. The migration history itself should be the main source of truth.

---

## 25. Rollbacks

The initial BASE3 migration contract intentionally defines only:

```php
public function up(): void;
```

There is no `down()` method.

Reason:

```text
Automatic rollback of real production data is often unsafe or misleading.
```

If a migration must be reverted, prefer a new forward migration that restores or adapts the schema intentionally.

Example:

```text
010_add_status_column
011_restore_status_defaults
012_deprecate_status_column
```

For development databases, manual reset tooling can be separate from the migration API.

---

## 26. Manual execution

The bootstrap integration supports automatic execution before request handling.

A project can also expose manual migration execution through:

```text
CLI command
admin display
worker job
deployment script
custom bootstrap
```

For projects that want only manual migration execution, keep the web bootstrap wired to `NoMigrationRunner` and use `DatabaseMigrationRunner` only in the manual execution path.

Possible future split:

```text
IMigrationRunner
  real runner for manual execution

IAutoMigrationRunner
  runner used by the web bootstrap
```

This split is not required for the first version, but it is useful if a project wants strict deployment control.

---

## 27. Hooks around migrations

The bootstrap dispatches:

```text
bootstrap.start
bootstrap.migrated
bootstrap.finish
```

Suggested interpretation:

```text
bootstrap.start
  all plugins have initialized, migrations have not run yet

bootstrap.migrated
  migration runner has completed successfully

bootstrap.finish
  request handling has completed
```

Do not rely on a normal hook listener to perform core database migrations if migrations are a guaranteed framework lifecycle step.

Use the explicit bootstrap call for the runner and use hooks around it for optional extension behavior.

---

## 28. Naming conventions

## 28.1 Provider names

Provider `getName()` values should be lowercase, stable, and technical.

Good:

```php
public static function getName(): string {
    return 'databasestatestoremigrationprovider';
}
```

Bad:

```php
public static function getName(): string {
    return 'Database State Store Migrations';
}
```

## 28.2 Migration names

Migration `getName()` values should include the owner and version.

Good:

```php
public static function getName(): string {
    return 'exampleplugin_001_create_tables';
}
```

Good:

```php
public static function getName(): string {
    return 'databasestore_004_add_expires_at_index';
}
```

Avoid names that are likely to collide across providers.

## 28.3 Versions

Use sortable strings.

Good:

```text
001
002
003
```

Good:

```text
2026063001
2026063002
```

Less good:

```text
v1
v10
v2
```

Natural sorting helps, but simple zero-padded versions are clearer.

---

## 29. Recommended workflow for adding a migration

1. Identify the schema owner.

```text
Framework backend?
Foundation implementation?
Plugin domain table?
Project-specific table?
```

2. Add or update the provider in that owner.

3. Create a new migration class with the next version.

4. Implement one small `up()` operation.

5. Add the migration class to `getMigrations()`.

6. Test on a fresh database.

7. Test on a database that has all previous migrations applied.

8. Do not edit previous migration classes after release.

---

## 30. Fresh installation behavior

On a fresh database, the database runner will:

```text
create base3_migrations
create base3_migration_locks
find active providers
run all migrations from each active provider
mark each successful migration as applied
```

If a technical backend also has an internal `ensureTable()`, it may create its minimal base table before or during normal service usage.

This is acceptable for private backend tables.

For domain tables, prefer migration steps as the main creation path.

---

## 31. Existing installation behavior

On an existing database, the runner will:

```text
read base3_migrations
skip already applied migrations
check checksums for applied migrations
run only missing migrations
mark newly successful migrations as applied
```

If an old migration file was modified, checksum validation fails and migration execution stops.

This is intentional.

---

## 32. Project-plugin responsibility

The project plugin is responsible for final composition.

It decides:

```text
whether the project has a database
which IDatabase implementation is active
whether automatic migrations should run
which migration runner is active
which optional database-backed services are active
```

It should not duplicate the table logic of framework services or reusable plugins.

Correct:

```php
$this->container->set(IStateStore::class, fn($c) => new DatabaseStateStore($c->get(IDatabase::class)), IContainer::SHARED);
```

Incorrect:

```text
Project plugin manually creates every table required by DatabaseStateStore.
```

Schema ownership belongs to the backend or plugin that owns the data structure.

---

## 33. Minimal project setup with automatic migrations

Example composition:

```php
$this->container
    ->set(
        IDatabase::class,
        fn($c) => new MysqlDatabase($c->get(IConfiguration::class)),
        IContainer::SHARED
    )
    ->set(
        IMigrationRunner::class,
        fn($c) => new DatabaseMigrationRunner(
            $c->get(IClassMap::class),
            $c->get(IDatabase::class)
        ),
        IContainer::SHARED
    );
```

The normal bootstrap then calls:

```php
$container->get(IMigrationRunner::class)->migrate();
```

No additional bootstrap logic is required.

---

## 34. Minimal project setup without automatic migrations

The default bootstrap already provides this behavior:

```php
$container->set(IMigrationRunner::class, fn() => new NoMigrationRunner(), IContainer::SHARED);
```

The normal bootstrap still calls:

```php
$container->get(IMigrationRunner::class)->migrate();
```

But nothing happens.

This is the correct default for projects without a database or projects that execute migrations manually outside the web request lifecycle.

---

## 35. Common mistakes

## 35.1 Running all migration classes directly

Bad:

```text
Find all IDatabaseMigration classes and run them all.
```

Why this is bad:

```text
A migration class may exist even when the related backend is not active.
```

Good:

```text
Find IDatabaseMigrationProvider classes.
Ask each provider whether it is active.
Run only migrations from active providers.
```

## 35.2 Making the bootstrap depend on IDatabase

Bad:

```text
Default bootstrap requires IDatabase.
```

Why this is bad:

```text
BASE3 must be able to run without a database.
```

Good:

```text
Default bootstrap registers NoMigrationRunner.
Project plugin replaces it when a database exists.
```

## 35.3 Putting all table setup into the project plugin

Bad:

```text
Project plugin creates tables for every selected service.
```

Why this is bad:

```text
The project plugin then owns schema details that belong to implementation modules.
```

Good:

```text
Project plugin wires services.
The selected services or their migration providers own schema setup.
```

## 35.4 Editing old migrations

Bad:

```text
Change 001_create_tables after it has shipped.
```

Good:

```text
Add 002_add_missing_column.
```

## 35.5 Heavy schema checks in normal services

Bad:

```text
Every repository method checks and repairs domain tables.
```

Good:

```text
Migration runner prepares domain schema once before request execution.
```

---

## 36. Summary

The BASE3 migration model is based on composition.

```text
No database by default.
No-op migration runner by default.
Project plugin enables the database-backed runner.
Migration providers own schema areas.
Providers decide whether they are active.
Migrations are immutable ordered steps.
Applied migrations are stored in base3_migrations.
Concurrent execution is guarded by a lock table.
Private backend tables may keep small ensureTable logic.
Domain schema belongs in real migrations.
```

This keeps BASE3 usable in small file-based projects, embedded host systems, and database-heavy applications without forcing one deployment model on every project.
