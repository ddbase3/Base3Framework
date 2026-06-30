# BASE3 Coding Conventions

## Purpose

This document defines coding conventions for BASE3 framework code, plugins, foundation plugins, and project plugins.

It is not only about formatting.

In BASE3, coding conventions also protect the framework architecture:

* dependency injection
* class map discovery
* plugin replaceability
* foundation contracts
* project-level composition
* MVC separation
* stable technical names
* immutable migration steps
* portable asset and path handling

A class can be formatted correctly and still violate BASE3 conventions if it creates unwanted plugin dependencies or bypasses the container and class map model.

---

## 1. General rules

BASE3 code should be:

* explicit
* typed
* modular
* replaceable
* discoverable where appropriate
* easy to wire through the container
* easy to test
* free of unnecessary hard dependencies between plugins

The main rule is:

```text id="f0u5p7"
Write code so that DI, PluginClassMap discovery, and plugin replacement continue to work.
```

---

## 2. PHP file header

PHP source files should start with:

```php id="er8p6w"
<?php declare(strict_types=1);
```

Use strict types for framework, plugin, foundation, and project code unless there is a specific compatibility reason not to.

A PHP source file should contain one main class, interface, trait, enum, or DTO.

---

## 3. Namespace and path convention

Namespaces must match the file path.

Framework example:

```text id="dzpv0i"
src/Settings/Database/DatabaseSettingsStore.php
```

Expected namespace:

```php id="u69eyv"
namespace Base3\Settings\Database;
```

Plugin example:

```text id="v78hyv"
plugin/ExamplePlugin/src/Service/ExampleService.php
```

Expected namespace:

```php id="n2fiu5"
namespace ExamplePlugin\Service;
```

This is important because the class map derives class names from paths.

If namespace and path do not match, class map discovery may fail.

---

## 4. Imports

Use `use` statements for dependencies.

Good:

```php id="a8rk8i"
use Base3\Settings\Api\ISettingsStore;
use ExamplePlugin\Api\IExampleService;
```

Avoid fully qualified class names inside method bodies unless there is a clear reason.

Good:

```php id="ea0gfn"
final class ExampleService {

	public function __construct(
		private readonly ISettingsStore $settingsStore
	) {}
}
```

Less readable:

```php id="q1odt9"
final class ExampleService {

	public function __construct(
		private readonly \Base3\Settings\Api\ISettingsStore $settingsStore
	) {}
}
```

---

## 5. Class layout

Preferred class layout:

```php id="f6m92j"
<?php declare(strict_types=1);

namespace ExamplePlugin\Service;

use ExamplePlugin\Api\IExampleService;

final class ExampleService implements IExampleService {

	public function __construct(
		private readonly string $name
	) {}

	public function getName(): string {
		return $this->name;
	}
}
```

General rules:

* opening braces stay on the same line
* keep one blank line after the class opening line
* group properties before methods
* put constructor before public runtime methods
* keep methods focused
* prefer `private` over `protected` unless extension is intended
* use `final` when a class is not designed for inheritance

---

## 6. Interfaces

Interfaces belong in `Api/` directories.

Example:

```text id="ykc9gq"
src/Api/IExampleService.php
```

Interface names should start with `I`.

Example:

```php id="wy9hmm"
interface IExampleService {

	public function doSomething(string $input): string;
}
```

Interfaces should be:

* small
* explicit
* stable
* implementation-neutral
* easy to mock
* free of project-specific assumptions

Avoid broad interfaces such as:

```php id="d6etmr"
interface IManager {

	public function handle(array $data): mixed;
}
```

Prefer specific contracts:

```php id="nbdrc0"
interface IQueryService {

	public function query(QueryStatement $statement): QueryResult;
}
```

---

## 7. DTOs and value objects

DTOs should be placed in `Dto/` or `Model/`, depending on the domain.

Use DTOs when several classes or plugins exchange the same structured data.

Good:

```php id="kek9xw"
final class QueryResult {

	/**
	 * @param array<int,array<string,mixed>> $rows
	 */
	public function __construct(
		private readonly array $rows,
		private readonly int $totalCount = 0
	) {}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function getRows(): array {
		return $this->rows;
	}

	public function getTotalCount(): int {
		return $this->totalCount;
	}
}
```

DTOs should usually be:

* simple
* typed
* serializable where useful
* free of service dependencies
* free of heavy business logic

Use DTOs instead of undocumented array structures when the same structure is shared across multiple components.

---

## 8. Exceptions

Domain-specific exception categories should live in an `Exception/` directory.

Example:

```text id="iu57og"
src/Exception/QueryValidationException.php
src/Exception/AccessDeniedException.php
```

Use shared exceptions when consumers need to catch a stable failure category.

Good:

```php id="hipfhq"
throw new QueryValidationException('Unknown field: ' . $field);
```

Avoid leaking implementation details into foundation exceptions.

Implementation-specific low-level exceptions should stay in the implementation plugin unless consumers need a generic catch point.

---

## 9. Technical names with `getName()`

Many BASE3 components use a static technical name.

Example:

```php id="mjrcc0"
public static function getName(): string {
	return 'connectionconfigdisplay';
}
```

Rules for `getName()`:

* use lowercase
* use a stable technical identifier
* do not use display labels
* do not use translated text
* do not include spaces
* avoid project-specific names in generic reusable classes
* keep names unique inside the relevant plugin/app

Good:

```php id="drq3nt"
public static function getName(): string {
	return 'mailimportjob';
}
```

Bad:

```php id="jwz8rg"
public static function getName(): string {
	return 'Mail Import Job';
}
```

Human-readable labels belong in language files, schemas, settings, or dedicated label methods.

---

## 10. Constructor injection

Runtime classes should receive dependencies through constructor injection.

Good:

```php id="nmsz2k"
public function __construct(
	private readonly ISettingsStore $settingsStore,
	private readonly IClassMap $classMap
) {}
```

Avoid pulling dependencies from the container inside normal runtime services.

Less good:

```php id="in9hl3"
public function __construct(
	private readonly IContainer $container
) {}

public function run(): void {
	$settingsStore = $this->container->get(ISettingsStore::class);
}
```

Use direct container access mainly in:

* bootstrap classes
* plugin `init()`
* composition code
* project plugins
* rare infrastructure cases where dynamic lookup is the purpose

---

## 11. Container usage

Use the container for known services.

Examples:

```php id="nmbikz"
IConfiguration::class
IRequest::class
IDatabase::class
ILogger::class
ISettingsStore::class
IStateStore::class
IEventManager::class
IMigrationRunner::class
```

Register services under interfaces when replacement is expected.

Good:

```php id="d0kvos"
$this->container->set(
	IExampleService::class,
	fn($c) => new ExampleService($c->get(ISettingsStore::class)),
	IContainer::SHARED
);
```

Avoid registering project-specific implementation classes as the only service identity when other plugins should depend on an abstraction.

---

## 12. Class map usage

Use the class map for discoverable components.

Examples:

```php id="bagl3o"
IPlugin::class
IOutput::class
IDisplay::class
IHookListener::class
IJob::class
ICheck::class
IJobExecutionPolicy::class
IConfigValueModeResolver::class
IDatabaseMigrationProvider::class
```

Use class map lookup when the caller knows the role but not the concrete class.

Example:

```php id="nfsie2"
$output = $classMap->getInstanceByInterfaceName(
	IOutput::class,
	$name
);
```

Do not create factories that only duplicate class map lookups.

Use a factory only when there is real runtime construction logic.

---

## 13. Plugin class conventions

Each plugin should normally have one main plugin class.

Example:

```php id="xj1zmo"
<?php declare(strict_types=1);

namespace ExamplePlugin;

use Base3\Api\IContainer;
use Base3\Api\IPlugin;

final class ExamplePlugin implements IPlugin {

	public function __construct(
		private readonly IContainer $container
	) {}

	public static function getName(): string {
		return 'exampleplugin';
	}

	public function init() {
		$this->container->set(
			self::getName(),
			$this,
			IContainer::SHARED
		);
	}
}
```

Plugin class rules:

* implement `IPlugin`
* receive `IContainer` in the constructor
* keep `init()` focused on service registration and composition
* avoid request handling in `init()`
* avoid heavy work in `init()`
* expose the plugin object only when useful

---

## 14. Plugin `init()` conventions

`init()` is composition code.

Good responsibilities:

* register services
* register aliases
* register fallback infrastructure
* register event listeners
* register project-specific bindings in project plugins
* expose the plugin object itself

Avoid:

* running imports
* executing jobs
* making external API calls
* rendering output
* reading request-specific state unless necessary
* hiding complex workflows in plugin initialization

Good:

```php id="b99i4q"
public function init() {
	$this->container
		->set(self::getName(), $this, IContainer::SHARED)
		->set(IExampleService::class, fn($c) => new ExampleService(
			$c->get(ISettingsStore::class)
		), IContainer::SHARED);
}
```

---

## 15. `NOOVERWRITE`

Use `NOOVERWRITE` for fallback services.

Example:

```php id="m8ck8t"
$this->container->set(
	IEventManager::class,
	fn() => new EventManager(),
	IContainer::SHARED | IContainer::NOOVERWRITE
);
```

This means:

```text id="gpyrzj"
Provide this implementation only if no other binding exists.
```

Use it for reusable plugins that provide default infrastructure.

Do not use it when the project must deliberately choose an implementation.

---

## 16. Plugin dependency rules

Reusable plugins should usually depend only on:

* BASE3 framework APIs
* their own classes
* foundation plugin APIs
* explicit extension targets

They should avoid importing concrete classes from unrelated normal plugins.

Recommended:

```text id="q43c37"
NormalPlugin -> Base3 APIs
NormalPlugin -> Foundation APIs
ProjectPlugin -> concrete normal plugins
```

Allowed exception:

```text id="mg7xse"
ExtensionPlugin -> Plugin it explicitly extends
```

If a plugin has no useful standalone meaning without another plugin, a direct dependency is acceptable and should be documented.

---

## 17. Foundation plugin conventions

Foundation plugins should define shared contracts.

They commonly contain:

```text id="dg8uex"
Api/
Dto/
Model/
Exception/
Proxy/
```

They should avoid final project implementation.

Good foundation content:

* interfaces
* DTOs
* shared models
* shared exception categories
* capability interfaces
* neutral proxies

Avoid in foundation plugins:

* project-specific services
* final container wiring
* host-specific implementations
* hardcoded storage backends
* direct dependencies on implementation plugins

Foundation plugins define slots.

Project plugins fill slots.

---

## 18. Project plugin conventions

A project plugin is the composition root for a project.

It may import concrete classes from several normal plugins.

This is intentional.

Example:

```php id="gfn9w2"
public function init() {
	$this->container
		->set(ISettingsStore::class, fn($c) => new DatabaseSettingsStore(
			$c->get(IDatabase::class)
		), IContainer::SHARED)

		->set(IQuerySchemaProvider::class, fn($c) => new ProjectQuerySchemaProvider(), IContainer::SHARED)

		->set(IReportConfigProvider::class, fn($c) => new ProjectReportConfigProvider(), IContainer::SHARED);
}
```

Project plugin rules:

* final service wiring belongs here
* project-specific configuration belongs here
* direct dependencies on normal plugins are allowed here
* keep project composition explicit
* avoid spreading final project wiring across unrelated plugins

---

## 19. Extension plugin conventions

An extension plugin intentionally extends another plugin.

Example:

```text id="hwpj8h"
MissionBayReporting extends MissionBay.
```

In that case, direct `use` statements to the base plugin are acceptable.

Rules:

* document the dependency
* make the extension role clear
* do not pretend the plugin is standalone
* keep the dependency direction one-way

---

## 20. Display and template conventions

Use parallel class and template paths.

Example:

```text id="vx3iaf"
src/Display/DataSchemaDisplay.php
tpl/Display/DataSchemaDisplay.php
```

The display class prepares data.

The template renders markup.

Good display pattern:

```php id="nm3ew7"
$this->view->setPath(DIR_PLUGIN . 'ExamplePlugin');
$this->view->setTemplate('Display/DataSchemaDisplay.php');
$this->view->assign('data', $data);
$this->view->assign('resolve', fn($src) => $this->assetResolver->resolve($src));

return $this->view->loadTemplate();
```

Template rules:

* render assigned data
* avoid business logic
* escape output where appropriate
* resolve assets through helpers
* keep JavaScript bootstrapping small
* avoid hardcoded deployment URLs

---

## 21. Asset conventions

Plugin assets live under:

```text id="wskepb"
plugin/<PluginName>/assets
```

Reference assets through logical plugin paths:

```text id="gdi9fb"
plugin/ExamplePlugin/assets/example/example.js
```

Use an asset resolver instead of hardcoded URLs.

Good:

```php id="l2efij"
$this->_['resolve']('plugin/ExamplePlugin/assets/example/example.js')
```

This keeps plugins portable across standalone and embedded deployments.

---

## 22. Language file conventions

Language files commonly live under:

```text id="dzqm8i"
lang/
└── Administration/
    ├── de.ini
    └── en.ini
```

Example:

```ini id="vw45vh"
[administration]
base3_admin_tab_provider = "Provider"
base3_admin_subtab_connectionconfigdisplay = "Connections"
```

Rules:

* keep keys stable
* use lowercase technical suffixes where possible
* keep display names out of `getName()`
* use language files for UI labels and navigation text

---

## 23. Local project files

Project plugins may contain a `local/` directory.

Use it for project-specific configuration files, presets, prompts, schemas, or sample data.

Example:

```text id="u3cc5m"
local/
├── Chatbot/
│   ├── default-agentflow.json
│   └── default-systemprompt.txt
├── DataHawk/
│   └── schema.json
└── Vizion/
    └── reports.json
```

Rules:

* reusable defaults belong to reusable plugins
* project-specific choices belong to project plugins
* frequently changing user data belongs to storage
* secrets should not be stored in repository-local files unless storage and permissions are explicitly managed

---

## 24. Settings, state, and configuration

Keep these concepts separate.

### Configuration

Use for static framework or deployment configuration.

### Settings Store

Use for editable grouped runtime settings.

### State Store

Use for operational runtime state such as cursors, progress, and timestamps.

### Config Value Resolver

Use for late value resolution from fixed values, configuration, environment variables, files, or custom modes.

Do not use one storage type as a replacement for all others.

---

## 25. Error handling

Use clear exceptions for invalid configuration, unsupported modes, missing required values, or inconsistent state.

Good:

```php id="zpu50k"
throw new RuntimeException('Missing connection id.');
```

Avoid vague messages:

```php id="abg2p1"
throw new RuntimeException('Error.');
```

Best-effort listeners may catch their own exceptions when failure should not affect the source operation.

Business-critical listeners should usually let errors bubble up.

Do not silently ignore errors unless best-effort behavior is explicitly intended.

---

## 26. Null, empty values, and fallback behavior

Be explicit about fallback behavior.

Use `null` when a value is intentionally missing.

Use `[]` for empty lists or empty structured data.

Use `""` only when the interface defines empty string as the missing-value representation.

Do not mix these casually.

Example:

```php id="yrz1rj"
$value = getenv($name);

if ($value === false) {
	return null;
}
```

Document missing-value behavior in interfaces when it matters.

---

## 27. Boolean normalization

When reading user input, request values, or config payloads, normalize booleans consistently.

Common accepted true values:

```text id="buxiqz"
1
true
yes
on
```

Example:

```php id="cdpq84"
private function normalizeBool(mixed $value): bool {
	if (is_bool($value)) {
		return $value;
	}

	if (is_int($value)) {
		return $value !== 0;
	}

	$value = strtolower(trim((string)$value));

	return in_array($value, ['1', 'true', 'yes', 'on'], true);
}
```

Keep this logic local when it is only needed for one form.

Move it to a shared helper only when several components need the same behavior.

---

## 28. Request data

Use `IRequest` for request data.

Avoid direct access to:

```php id="dsn3kc"
$_GET
$_POST
$_REQUEST
```

inside runtime classes.

Good:

```php id="j3cidq"
$action = strtolower(trim((string)$this->request->request('action', '')));
```

Normalize and validate request values before using them.

---

## 29. Database access

Use the framework database abstraction where possible.

Keep SQL-building localized.

Validate and quote values.

Avoid scattering raw SQL across unrelated display classes unless the display is explicitly a thin administrative tool.

For reusable query behavior, prefer services or repositories.

---

## 30. Events

Event classes should live under:

```text id="bycvpn"
src/Event/
```

Listener classes should live under:

```text id="xnh75l"
src/Listener/
```

Event classes should be small data objects.

Good:

```php id="otm9g8"
final class ExampleStartedEvent extends BaseEvent {

	public function __construct(
		private readonly string $id
	) {}

	public function getId(): string {
		return $this->id;
	}
}
```

Listener methods should describe the event:

```php id="nci5t5"
public function onExampleStarted(ExampleStartedEvent $event): void {
	// ...
}
```

Register listeners in plugin `init()` or composition code.

---

## 31. Hooks

Hook listeners are lifecycle-oriented.

They may be discovered before plugin `init()`.

Therefore hook listener constructor dependencies must already be available early.

Use hooks for framework lifecycle extension.

Use events for runtime domain notifications.

---

## 32. Jobs

Jobs should have stable technical names.

Example:

```php id="jqyb53"
public static function getName(): string {
	return 'examplecleanupjob';
}
```

Jobs should keep runtime logic inside `go()`.

Use settings or configuration for job options.

Use state for last-run timestamps, progress, cursors, and operational markers.

Policy-controlled jobs should expose clear policy definitions.

---

## 33. Comments and PHPDoc

Use comments where they explain intent, constraints, or non-obvious behavior.

Do not comment obvious code.

Good:

```php id="czp3ux"
/**
 * @param array<string,mixed> $trace
 */
public function __construct(
	private readonly array $trace = []
) {}
```

Use PHPDoc for array shapes and generic-style arrays.

Examples:

```php id="rvpnmv"
/**
 * @return array<string,array>
 */
public function getModeSchemas(): array {
	// ...
}
```

Avoid large comments that duplicate the implementation without adding meaning.

---

## 34. Tests

Tests should mirror the source structure where practical.

Example:

```text id="wfkj86"
src/Service/ExampleService.php
test/Service/ExampleServiceTest.php

src/Dto/QueryStatement.php
test/Dto/QueryStatementTest.php
```

Test:

* DTO behavior
* resolver behavior
* validation behavior
* service wiring when important
* plugin `init()` when it registers important services
* class map discoverable names when they are part of the public plugin API

Use fakes for foundation interfaces when testing consumer plugins.

---

## 35. Formatting summary

Use a consistent style across BASE3 code.

Recommended style:

```php id="dtvg9z"
final class ExampleService implements IExampleService {

	public function __construct(
		private readonly ISettingsStore $settingsStore
	) {}

	public function doSomething(string $input): string {
		return trim($input);
	}
}
```

Formatting rules:

* class and method braces on the same line
* blank line after class opening
* constructor promotion where appropriate
* visibility on all properties and methods
* typed parameters and return types where practical
* no unnecessary abbreviations
* no mixed indentation styles inside one file

---

## 36. Security conventions

Do not log secrets.

Do not echo raw request data without escaping.

Do not store high-risk secrets as fixed config values unless the storage backend is protected.

Prefer config value definitions for secrets:

```php id="qae6xh"
[
	'mode' => 'env',
	'name' => 'SERVICE_API_KEY'
]
```

or:

```php id="anm9ii"
[
	'mode' => 'file',
	'path' => DIR_LOCAL . 'secret/service.key'
]
```

Be careful with templates that embed JSON into JavaScript.

Use `json_encode()` and escape HTML output where appropriate.

---

## 37. Portability conventions

BASE3 can run standalone or embedded.

Portable plugin code should avoid assumptions about:

* public URL structure
* host system paths
* user implementation
* session implementation
* access control implementation
* settings backend
* asset URL layout
* plugin installation path beyond class map conventions

Use framework interfaces and resolvers.

---

## 38. Common mistakes

### Using the container everywhere

Use constructor injection in runtime classes.

Use the container in bootstrap and plugin composition.

### Depending on concrete normal plugins from reusable plugins

Move shared contracts into a foundation plugin.

Wire concrete implementations in the project plugin.

### Putting final implementation choices into foundation plugins

Foundation plugins define slots.

Project plugins fill slots.

### Treating `getName()` as a label

`getName()` is a technical identifier.

Use language files for labels.

### Hardcoding asset URLs

Use the asset resolver.

### Creating factories for class map lookups

Use the class map unless real construction logic is needed.

### Doing heavy work in plugin `init()`

Register services in `init()`.

Run behavior later.

### Mixing settings and state

Settings are editable configuration datasets.

State is operational runtime data.

---

## 39. Checklist for a new class

Before adding a class, check:

```text id="lr62lc"
Does the namespace match the path?
Does it need an interface?
Should it be registered in the container?
Should it be discovered by the class map?
Does it need a stable getName()?
Are dependencies injected through the constructor?
Does it depend only on allowed packages/plugins?
Does it belong in framework, foundation, feature plugin, implementation plugin, or project plugin?
Does it need tests?
```

---

## 40. Checklist for a new plugin

Before adding a plugin, check:

```text id="w386nv"
Does it have a clear responsibility?
Does it have one main IPlugin class?
Are PHP classes under src/?
Do namespaces match paths?
Are templates under tpl/?
Are assets under assets/?
Are language files under lang/?
Are project-specific files under local/?
Does init() only register services and listeners?
Does it avoid unwanted dependencies on normal plugins?
Should shared contracts move into a foundation plugin?
```

---

## 41. Checklist for a new foundation area

Before adding a foundation plugin, check:

```text id="fh9uee"
Do multiple plugins need these contracts?
Should implementations be replaceable?
Can the contracts stay implementation-neutral?
Are DTOs needed for shared data structures?
Are shared exceptions needed?
Can consumers depend only on Api/ classes?
Will final wiring stay in project plugins?
```

A foundation plugin is justified when it prevents unwanted dependencies and defines a stable extension area.

---

## 42. Database migration conventions

Migration providers and migration steps are part of the framework architecture and should follow the same discoverability and DI rules as other BASE3 components.

Rules:

* put migration providers and steps under `src/Migration/`
* make providers implement `IDatabaseMigrationProvider`
* make steps implement `IDatabaseMigration`
* use stable lowercase `getName()` values
* treat released migration steps as immutable
* add a new migration step instead of editing an old released step
* inject `IDatabase` into migration steps through the constructor
* keep each migration focused on one schema or data change
* do not run migrations in plugin `init()`
* let the configured `IMigrationRunner` execute migrations after plugin initialization

Good migration name:

```php
public static function getName(): string {
	return 'exampleplugin_002_add_status_index';
}
```

Bad migration name:

```php
public static function getName(): string {
	return 'Add Status Index';
}
```

Migration providers should be active only when their schema owner is relevant to the current runtime composition.

---

## 43. Summary

BASE3 coding conventions combine formatting and architecture.

The formatting style should be consistent.

The architectural style should protect modularity.

In short:

```text id="lz6uzv"
Use strict types.
Match namespaces to paths.
Use constructor injection.
Register known services in the container.
Discover extension components through PluginClassMap.
Use stable technical getName() values.
Keep templates parallel to display classes.
Resolve assets through resolvers.
Keep foundation plugins implementation-light.
Keep project wiring in project plugins.
Avoid unwanted dependencies between reusable plugins.
```
