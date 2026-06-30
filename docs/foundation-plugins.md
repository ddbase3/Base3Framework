# BASE3 Framework Foundation Plugins

## Purpose

This document explains the role of **Foundation Plugins** in the BASE3 framework.

It is written for developers who want to understand:

* what a Foundation Plugin is
* why Foundation Plugins exist
* why they usually contain interfaces, DTOs, models, exceptions, and neutral helper classes
* why they should avoid concrete project wiring
* how they define plugin slots
* how they help build replaceable plugin architectures
* how project plugins bind the final implementations into the container
* how Foundation Plugins reduce unwanted dependencies between feature plugins
* how Foundation Plugins relate to dependency injection and the class map

After reading this document, a developer should understand why Foundation Plugins are an architectural boundary, not just a folder convention.

---

## 1. What a Foundation Plugin is

A Foundation Plugin is a BASE3 plugin whose main purpose is to define shared contracts.

It usually provides:

* interfaces
* DTOs
* value objects
* models
* exceptions
* schema objects
* neutral proxy classes
* marker interfaces
* extension point contracts
* plugin slot definitions

It usually does **not** provide the final business implementation.

A useful shortcut is:

```text id="k2k1cv"
Foundation Plugin = shared contracts + stable data structures + extension slots
```

A Foundation Plugin defines what can exist.

Other plugins decide what actually exists in a project.

---

## 2. Why Foundation Plugins exist

In a modular plugin system, feature plugins often need to talk about the same concepts.

Example concepts:

* entity data
* file storage
* query services
* agent resources
* communication transports
* media objects
* UI components
* user preferences
* external providers
* vector stores
* parsers
* report schemas

Without a shared contract layer, plugins start depending directly on each other.

That leads to unwanted coupling.

Example problem:

```text id="m3m4ws"
Plugin A needs an entity data service.
Plugin B provides one implementation.
Plugin C provides another implementation.
Plugin A should not depend directly on Plugin B or Plugin C.
```

A Foundation Plugin solves this by defining the interface:

```php id="jj53hm"
interface IEntityDataService {
	// contract only
}
```

Then a project plugin decides which implementation is active.

---

## 3. The dependency problem

Without Foundation Plugins, plugin dependencies often become too concrete.

Less ideal:

```mermaid id="l77l6a"
flowchart TD
	A[Reporting Plugin] --> B[Specific CRM Plugin]
	A --> C[Specific File Plugin]
	A --> D[Specific Query Plugin]
```

The reporting plugin now depends on specific implementations.

That makes replacement difficult.

With a Foundation Plugin:

```mermaid id="jho889"
flowchart TD
	F[Resource Foundation] --> I1[IEntityDataService]
	F --> I2[IFileStorage]
	F --> I3[IQueryService]

	A[Reporting Plugin] --> I1
	A --> I2
	A --> I3

	B[CRM Plugin] --> I1
	C[File Plugin] --> I2
	D[Query Plugin] --> I3

	P[Project Plugin] --> B
	P --> C
	P --> D
```

The feature plugin depends on contracts.

The project plugin chooses implementations.

---

## 4. Main architectural goal

The main goal is replaceability.

A Foundation Plugin makes it possible to replace one implementation plugin with another without changing all consuming plugins.

```text id="go42rg"
Consumers depend on foundation interfaces.
Implementations live in separate plugins.
Project plugins wire the final choice.
```

This gives BASE3 applications:

* lower coupling
* clearer dependency direction
* better testability
* better plugin reuse
* cleaner integration points
* easier host-specific replacement
* fewer accidental dependencies
* more explicit project composition

---

## 5. Typical Foundation Plugin structure

A Foundation Plugin commonly looks like this:

```text id="zdx7qy"
ResourceFoundation/
├── LICENSE
├── README.md
├── src/
│   ├── Api/
│   │   ├── IEntityDataService.php
│   │   ├── IEntityFileService.php
│   │   ├── IFileStorage.php
│   │   ├── IQueryCompiler.php
│   │   ├── IQuerySchemaProvider.php
│   │   └── IQueryService.php
│   ├── Dto/
│   │   ├── FieldMetadata.php
│   │   ├── JoinMetadata.php
│   │   ├── QueryResult.php
│   │   ├── QueryStatement.php
│   │   └── TableMetadata.php
│   ├── Exception/
│   │   ├── AccessDeniedException.php
│   │   └── QueryValidationException.php
│   ├── Proxy/
│   │   ├── EntityDataProxy.php
│   │   └── EntityFileProxy.php
│   └── ResourceFoundationPlugin.php
├── test/
│   ├── Dto/
│   ├── Proxy/
│   └── ResourceFoundationPluginTest.php
└── VERSION
```

The important directories are:

```text id="g3j6ba"
Api/
Dto/
Exception/
Model/
Proxy/
```

The exact set depends on the foundation domain.

---

## 6. `Api/`

The `Api/` directory contains interfaces.

These interfaces are the most important part of a Foundation Plugin.

Examples:

```php id="mk8tuk"
IEntityDataService
IEntityFileService
IFileStorage
IQueryCompiler
IQuerySchemaProvider
IQueryService
```

Interfaces define what consumers may depend on.

They should be:

* small
* stable
* implementation-neutral
* project-neutral
* host-neutral
* easy to mock
* clear about input and output types

A Foundation Plugin should not define an interface only because a class exists.

It should define an interface when replacement is intended.

---

## 7. `Dto/`

The `Dto/` directory contains data transfer objects.

DTOs describe shared data shapes.

Examples:

```php id="lxuw7k"
FieldMetadata
JoinMetadata
QueryResult
QueryStatement
TableMetadata
```

DTOs are useful when multiple plugins need to exchange structured data without depending on a concrete implementation service.

A DTO should usually be:

* simple
* typed
* serializable when possible
* free of infrastructure dependencies
* stable enough to be shared across plugins

DTOs should not contain heavy business logic.

They describe data.

---

## 8. `Model/`

Some Foundation Plugins may include models.

A model in a Foundation Plugin should represent a shared concept, not a project-specific persistence implementation.

Good examples:

```text id="o2etck"
ResourceDescriptor
ConnectionDefinition
ToolDefinition
SchemaDefinition
ProviderCapability
```

Avoid putting project-specific active record classes or database-bound implementation models into a Foundation Plugin.

Those belong in implementation plugins.

---

## 9. `Exception/`

The `Exception/` directory contains shared exception types.

Examples:

```php id="dizvq8"
AccessDeniedException
QueryValidationException
```

Shared exceptions are useful when multiple plugins need to react to the same failure category without knowing the implementation plugin.

Example:

```php id="bqoa8n"
try {
	$result = $queryService->query($statement);
}
catch (QueryValidationException $e) {
	// show validation error
}
```

The consumer catches a foundation exception.

It does not care which implementation plugin threw it.

---

## 10. `Proxy/`

Some Foundation Plugins may include neutral proxy classes.

A proxy can provide a stable access point while delegating to a replaceable implementation.

Example concepts:

```text id="rtkuxm"
EntityDataProxy
EntityFileProxy
```

A proxy can be useful when:

* several consumers need the same access pattern
* the actual implementation is replaceable
* fallback or guard logic should stay centralized
* calls should be normalized before reaching the real service

A proxy should still avoid becoming the real business implementation.

Its job is to bridge, delegate, or normalize.

---

## 11. Foundation plugin class

A Foundation Plugin can still contain a plugin class.

Example:

```php id="wpd463"
final class ResourceFoundationPlugin implements IPlugin {

	public function __construct(
		private readonly IContainer $container
	) {}

	public static function getName(): string {
		return 'resourcefoundationplugin';
	}

	public function init() {
		$this->container->set(self::getName(), $this, IContainer::SHARED);
	}
}
```

The plugin class may register:

* the plugin instance itself
* neutral proxies
* fallback no-op implementations
* schema registries
* metadata services
* compatibility helpers

But it should avoid binding a project-specific final implementation unless that implementation is truly generic.

---

## 12. What Foundation Plugins should avoid

A Foundation Plugin should usually avoid:

* concrete project services
* host-specific implementation logic
* database-specific query execution
* hardcoded storage backends
* hardcoded provider choices
* final service wiring for applications
* large business workflows
* runtime decisions that belong to the project
* direct dependencies on implementation plugins

The point is not to do everything.

The point is to define what other plugins can depend on safely.

---

## 13. Dependency direction

Foundation Plugins should sit below feature and implementation plugins.

Recommended direction:

```mermaid id="h66tmz"
flowchart TD
	F[Foundation Plugin] --> A[Shared Interfaces]
	F --> D[DTOs]
	F --> E[Exceptions]

	C1[Consumer Plugin] --> A
	C2[Another Consumer Plugin] --> A

	I1[Implementation Plugin A] --> A
	I2[Implementation Plugin B] --> A

	P[Project Plugin] --> I1
	P --> I2
	P --> A
```

Wrong direction:

```mermaid id="gzlf76"
flowchart TD
	F[Foundation Plugin] --> I[Implementation Plugin]
```

A Foundation Plugin depending on an implementation plugin defeats its purpose.

---

## 14. Consumer plugins

A consumer plugin uses Foundation Plugin interfaces.

Example:

```php id="d23e8q"
final class ReportDisplay implements IDisplay {

	public function __construct(
		private readonly IQueryService $queryService
	) {}

	public static function getName(): string {
		return 'reportdisplay';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$result = $this->queryService->query(
			new QueryStatement(...)
		);

		return $this->render($result);
	}
}
```

The consumer does not know which plugin implements `IQueryService`.

That is intentional.

---

## 15. Implementation plugins

An implementation plugin provides concrete classes for Foundation Plugin interfaces.

Example:

```php id="pr6a9y"
final class DatabaseQueryService implements IQueryService {

	public function __construct(
		private readonly IDatabase $database,
		private readonly IQueryCompiler $compiler
	) {}

	public function query(QueryStatement $statement): QueryResult {
		// concrete implementation
	}
}
```

This plugin may register a default implementation.

```php id="l4x2mu"
$this->container->set(
	IQueryService::class,
	fn($c) => new DatabaseQueryService(
		$c->get(IDatabase::class),
		$c->get(IQueryCompiler::class)
	),
	IContainer::SHARED | IContainer::NOOVERWRITE
);
```

Using `NOOVERWRITE` keeps the implementation replaceable.

A project plugin can still override the binding earlier or later depending on composition order.

---

## 16. Project plugins

A project plugin is where final composition happens.

It decides:

* which implementation should be active
* which backend should be used
* which adapters should be combined
* which foundation slots are filled
* which defaults should be overridden
* which host-specific services should be bound

Example:

```php id="c3kumx"
final class ProjectCompositionPlugin implements IPlugin {

	public function __construct(
		private readonly IContainer $container
	) {}

	public static function getName(): string {
		return 'projectcompositionplugin';
	}

	public function init() {
		$this->container
			->set(IQueryCompiler::class, fn($c) => new MysqlQueryCompiler(), IContainer::SHARED)
			->set(IQueryService::class, fn($c) => new DatabaseQueryService(
				$c->get(IDatabase::class),
				$c->get(IQueryCompiler::class)
			), IContainer::SHARED)
			->set(IFileStorage::class, fn($c) => new LocalFileStorage(
				DIR_LOCAL . 'files/'
			), IContainer::SHARED);
	}
}
```

This plugin owns the final wiring.

Foundation and feature plugins stay reusable.

---

## 17. Why final wiring belongs in a project plugin

The final service graph is a project decision.

Different projects may want different implementations.

Example:

```text id="s6jpk2"
Project A:
	IFileStorage => LocalFileStorage

Project B:
	IFileStorage => S3FileStorage

Project C:
	IFileStorage => HostManagedFileStorage
```

The consuming plugin should not change.

The Foundation Plugin should not change.

Only the project composition changes.

This is the main architectural value.

---

## 18. Plugin slots

A Foundation Plugin defines plugin slots.

A plugin slot is a replaceable interface that other plugins can fill.

Examples:

```php id="kwjjmv"
IFileStorage
IQueryService
IQueryCompiler
IQuerySchemaProvider
IEntityDataService
IEntityFileService
```

A good plugin slot has:

* a clear interface
* documented responsibilities
* stable method signatures
* DTOs for structured inputs and outputs
* shared exceptions for known failures
* no dependency on one implementation plugin

The slot is filled by registering an implementation in the container.

---

## 19. Slot filling through DI

A slot becomes active when the container has a binding.

```php id="jiujoj"
$container->set(
	IFileStorage::class,
	fn($c) => new LocalFileStorage(DIR_LOCAL . 'files/'),
	IContainer::SHARED
);
```

Consumers only type-hint:

```php id="xe20qs"
public function __construct(
	private readonly IFileStorage $fileStorage
) {}
```

This is the complete replacement mechanism.

The consumer does not ask which plugin provided the service.

---

## 20. Optional implementations

Some implementation plugins may provide a default without forcing it.

```php id="l328lt"
$container->set(
	IQueryCompiler::class,
	fn($c) => new DefaultQueryCompiler(),
	IContainer::SHARED | IContainer::NOOVERWRITE
);
```

This means:

```text id="btdmrq"
Use this implementation if nothing better was registered.
```

That pattern is useful for reusable plugins.

It should not be used when a project explicitly requires one implementation and should fail otherwise.

---

## 21. Avoiding unwanted dependencies

A Foundation Plugin helps avoid dependencies like this:

```text id="ps27wa"
ReportPlugin -> SpecificDatabasePlugin
UploadPlugin -> SpecificStoragePlugin
AgentPlugin -> SpecificProviderPlugin
```

Instead:

```text id="x56ez9"
ReportPlugin -> ResourceFoundation
SpecificDatabasePlugin -> ResourceFoundation
ProjectPlugin wires IQueryService
```

The project plugin becomes the composition root.

That is where concrete choices belong.

---

## 22. Foundation Plugin versus normal plugin

A normal feature plugin usually provides behavior.

A Foundation Plugin usually provides structure.

### Normal plugin

Typically contains:

* services
* displays
* jobs
* forms
* domain logic
* external integrations
* runtime behavior

### Foundation Plugin

Typically contains:

* interfaces
* DTOs
* exceptions
* shared models
* neutral proxies
* extension contracts
* plugin slots

A Foundation Plugin may contain small helpers, but it should avoid becoming a feature implementation.

---

## 23. Foundation Plugin versus framework core

Not every shared interface belongs in the BASE3 framework core.

A Foundation Plugin is useful when the concept is important across several plugins, but not universal enough for the core.

Examples:

```text id="w9uowj"
query resources
media handling
agent resources
communication channels
UI component contracts
provider abstraction
```

This keeps the framework core smaller.

It also lets domains evolve independently.

---

## 24. Foundation Plugin versus implementation plugin

Foundation Plugin:

```text id="hvpzgy"
defines IFileStorage
defines FileMetadata DTO
defines FileStorageException
```

Implementation Plugin:

```text id="vebsgo"
implements LocalFileStorage
implements RemoteFileStorage
implements EncryptedFileStorage
```

Project Plugin:

```text id="h7lqj5"
binds IFileStorage => EncryptedFileStorage
```

Each layer has a clear responsibility.

---

## 25. Class Map and Foundation Plugins

Foundation Plugin classes are discoverable like any other plugin class.

That matters for:

* plugin class discovery
* checks
* schema providers
* marker interfaces
* optional extension definitions

However, most Foundation Plugin value comes from interfaces and DTOs, not from class-map lookup.

Use class-map-discovered classes when you want multiple implementations to be found automatically.

Use DI bindings when one active implementation should be selected for a service slot.

---

## 26. Container and Foundation Plugins

Foundation Plugins define the types.

The container decides which concrete object is returned for those types.

Example:

```php id="y5xvao"
IQueryService::class => DatabaseQueryService
```

The Foundation Plugin owns:

```php id="gvwfiv"
IQueryService
QueryStatement
QueryResult
QueryValidationException
```

The project or implementation plugin owns:

```php id="e0okif"
DatabaseQueryService
```

This separation keeps the contract stable while allowing the implementation to change.

---

## 27. Designing a foundation interface

A good foundation interface should be small and explicit.

Good:

```php id="xfath0"
interface IQueryService {

	public function query(QueryStatement $statement): QueryResult;
}
```

Less good:

```php id="mic825"
interface IResourceEverythingService {

	public function doAnything(array $payload): mixed;
}
```

A good interface says what the slot is for.

It should not become an untyped escape hatch.

---

## 28. Designing foundation DTOs

A good foundation DTO should make plugin communication predictable.

Example:

```php id="x70t7p"
final class QueryResult {

	/**
	 * @param array<int,array<string,mixed>> $rows
	 */
	public function __construct(
		private readonly array $rows,
		private readonly int $totalCount = 0
	) {}

	public function getRows(): array {
		return $this->rows;
	}

	public function getTotalCount(): int {
		return $this->totalCount;
	}
}
```

DTOs should reduce ambiguity between plugins.

They are especially helpful when a method would otherwise accept or return loose arrays with undocumented structure.

---

## 29. Designing foundation exceptions

A shared exception should represent a stable failure category.

Good examples:

```text id="g4k31c"
AccessDeniedException
QueryValidationException
StorageUnavailableException
UnsupportedCapabilityException
```

Avoid overly specific exceptions that leak one implementation's internals.

Less good:

```text id="o348ze"
MysqlConnectionStringMalformedException
LocalDiskFolderMissingException
```

Those belong in implementation plugins unless consumers need to catch them generically.

---

## 30. Designing proxy classes

A proxy in a Foundation Plugin should have a clear reason.

Possible reasons:

* normalize calls before delegation
* add guard checks
* provide a stable facade over a replaceable slot
* reduce repeated container lookups in older code
* preserve compatibility while moving toward DI

Example concept:

```php id="gqajg8"
final class EntityDataProxy {

	public function __construct(
		private readonly IEntityDataService $entityDataService
	) {}

	public function get(string $entityType, string $id): array {
		return $this->entityDataService->get($entityType, $id);
	}
}
```

A proxy should not become the hidden place where final project wiring happens.

---

## 31. Foundation plugin `init()` style

A Foundation Plugin's `init()` should usually be small.

Good:

```php id="op3xkd"
public function init() {
	$this->container->set(self::getName(), $this, IContainer::SHARED);
}
```

Acceptable when useful:

```php id="d762ff"
public function init() {
	$this->container
		->set(self::getName(), $this, IContainer::SHARED)
		->set(EntityDataProxy::class, fn($c) => new EntityDataProxy(
			$c->get(IEntityDataService::class)
		), IContainer::SHARED | IContainer::NOOVERWRITE);
}
```

Be careful with this:

```php id="mfqtj9"
public function init() {
	$this->container->set(
		IEntityDataService::class,
		fn($c) => new ProjectSpecificEntityDataService(),
		IContainer::SHARED
	);
}
```

That is usually not a Foundation Plugin responsibility.

---

## 32. Project composition pattern

A clean project architecture often has these layers:

```mermaid id="rlxbce"
flowchart TD
	F1[Resource Foundation]
	F2[Communication Foundation]
	F3[UI Foundation]

	I1[Database Resource Plugin]
	I2[Remote Resource Plugin]
	I3[Communication Provider Plugin]
	I4[UI Components Plugin]

	C1[Reporting Plugin]
	C2[Agent Plugin]
	C3[Admin Plugin]

	P[Project Composition Plugin]

	C1 --> F1
	C2 --> F1
	C2 --> F2
	C3 --> F3

	I1 --> F1
	I2 --> F1
	I3 --> F2
	I4 --> F3

	P --> I1
	P --> I3
	P --> I4
	P --> F1
	P --> F2
	P --> F3
```

The project plugin decides the final service graph.

---

## 33. Multiple project plugins

A project does not need to have only one composition plugin.

It may use several central project plugins.

Example:

```text id="cq58sf"
ProjectInfrastructurePlugin
ProjectProviderPlugin
ProjectAdminPlugin
ProjectAgentPlugin
```

Each may wire one area.

The important point is that final wiring remains centralized and intentional.

It should not be scattered across unrelated feature plugins.

---

## 34. Capability-based extension

Sometimes one interface is too broad.

A Foundation Plugin can define capability interfaces.

Example:

```php id="xlblod"
interface ISupportsSchemaInspection {

	public function getSchema(): array;
}
```

or:

```php id="b0k11x"
interface ISupportsFileStreaming {

	public function openStream(string $id);
}
```

This lets consumers ask for specific capabilities instead of depending on one large service interface.

Capability interfaces are useful when implementations vary.

---

## 35. Avoiding circular dependencies

Foundation Plugins are often introduced to avoid circular dependencies.

Bad:

```text id="y1en8u"
Plugin A depends on Plugin B
Plugin B depends on Plugin A
```

Better:

```text id="sy9j99"
Plugin A depends on Foundation
Plugin B depends on Foundation
Project Plugin wires A and B together
```

If two plugins need each other's interfaces, those interfaces may belong in a Foundation Plugin.

---

## 36. Testing with Foundation Plugins

Foundation Plugins make testing easier.

A consumer can test against a fake implementation of a foundation interface.

Example:

```php id="wjrvhe"
final class FakeQueryService implements IQueryService {

	public function query(QueryStatement $statement): QueryResult {
		return new QueryResult([
			['id' => 1, 'title' => 'Example']
		]);
	}
}
```

The test does not need the real database plugin.

That is one of the main benefits of interface-first design.

---

## 37. Versioning Foundation Plugins

Foundation Plugin changes should be handled carefully because many plugins may depend on them.

Safe changes:

* adding a new DTO
* adding a new interface
* adding optional methods through a new capability interface
* adding a new exception type
* adding documentation
* adding tests

Risky changes:

* changing method signatures
* changing DTO constructor arguments without compatibility
* renaming interfaces
* changing exception inheritance unexpectedly
* changing semantic meaning of existing methods

Treat Foundation Plugin APIs as public contracts.

---

## 38. Documentation responsibility

A Foundation Plugin should document its contracts.

For each important interface, document:

* purpose
* expected inputs
* expected outputs
* error behavior
* whether calls may have side effects
* whether implementations should be idempotent
* which exceptions may be thrown
* whether return DTOs are immutable or mutable

A Foundation Plugin without contract documentation becomes hard to implement correctly.

---

## 39. Naming conventions

Use names that describe the domain slot.

Good:

```text id="locmk9"
IQueryService
IFileStorage
IEntityDataService
IConnectionDriverDefinition
IParserService
IVectorStoreService
```

Less good:

```text id="dvgwy0"
IManager
IHandler
IAdapter
IService
```

Generic names are sometimes unavoidable, but Foundation Plugins benefit from precise interfaces.

---

## 40. Common mistakes

### Putting implementation into the foundation layer

If a class talks to a concrete database schema, external API, host API, or project-specific file layout, it probably does not belong in a Foundation Plugin.

### Binding final implementations too early

A Foundation Plugin should not usually decide:

```php id="onxrdt"
IFileStorage => LocalFileStorage
```

That is a project decision.

### Creating one huge foundation interface

Large interfaces make replacement harder.

Prefer smaller interfaces and capability interfaces.

### Letting feature plugins depend on implementation plugins

Use the Foundation Plugin as the shared contract layer instead.

### Scattering final wiring across many plugins

Centralize final composition in one or more project plugins.

### Using arrays where a DTO is needed

If several plugins exchange the same array shape, create a DTO.

### Hiding dependencies behind container lookups

Runtime classes should use constructor injection.

---

## 41. Practical rules

Use Foundation Plugins to define shared contracts.

Keep Foundation Plugins implementation-light.

Put interfaces in `src/Api`.

Put shared data objects in `src/Dto` or `src/Model`.

Put shared exception categories in `src/Exception`.

Use proxies only when they stay neutral and delegate to replaceable slots.

Do not bind project-specific implementations in Foundation Plugins.

Let implementation plugins provide concrete services.

Let project plugins decide final container bindings.

Use `NOOVERWRITE` only for fallback defaults that should remain replaceable.

Avoid direct dependencies from consumers to implementation plugins.

Use DTOs instead of undocumented array protocols.

Treat Foundation Plugin APIs as public contracts.

---

## 42. Summary

Foundation Plugins are architectural contract packages.

They define the shared language between BASE3 plugins:

```text id="c9z5ug"
interfaces
DTOs
models
exceptions
plugin slots
neutral proxies
```

They are not primarily feature implementation plugins.

Their purpose is to make plugins replaceable and composable.

The intended dependency flow is:

```text id="tl337e"
Consumer Plugin -> Foundation Plugin
Implementation Plugin -> Foundation Plugin
Project Plugin -> final container wiring
```

The Foundation Plugin defines the slot.

The implementation plugin provides a possible implementation.

The project plugin decides which implementation is active.

In short:

```text id="rge1it"
Foundation Plugins define architecture.
Implementation Plugins provide behavior.
Project Plugins wire the application.
```
