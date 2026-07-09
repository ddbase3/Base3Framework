# BASE3 Extension Cookbook

## Purpose

This document is a practical cookbook for extending BASE3.

It explains common extension tasks:

* creating a plugin
* adding a display or output
* adding a service
* adding a job
* adding a check
* adding an event listener
* adding a config value mode
* wiring a project plugin
* creating a new foundation area
* avoiding unwanted dependencies between plugins

This document is intentionally practical.

For deeper explanations, read the subsystem documents.

---

## 1. Create a new plugin

Recommended structure:

```text
plugin/
└── ExamplePlugin/
    ├── assets/
    ├── docs/
    ├── lang/
    ├── local/
    ├── README.md
    ├── src/
    │   └── ExamplePlugin.php
    ├── test/
    ├── tpl/
    └── VERSION
```

Minimal structure:

```text
plugin/
└── ExamplePlugin/
    ├── src/
    │   └── ExamplePlugin.php
    └── VERSION
```

The plugin class:

```php
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

The class must be under:

```text
plugin/ExamplePlugin/src/ExamplePlugin.php
```

with namespace:

```php
namespace ExamplePlugin;
```

---

## 2. Add a service

Create an interface if replacement is expected:

```text
src/Api/IExampleService.php
```

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Api;

interface IExampleService {

	public function doSomething(string $input): string;
}
```

Create an implementation:

```text
src/Service/ExampleService.php
```

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Service;

use ExamplePlugin\Api\IExampleService;

final class ExampleService implements IExampleService {

	public function doSomething(string $input): string {
		return strtoupper($input);
	}
}
```

Register it in the plugin:

```php
$this->container->set(
	IExampleService::class,
	fn($c) => new ExampleService(),
	IContainer::SHARED
);
```

Consumers should depend on the interface:

```php
public function __construct(
	private readonly IExampleService $exampleService
) {}
```

---

## 3. Add a display with template

Create:

```text
src/Display/ExampleDisplay.php
tpl/Display/ExampleDisplay.php
```

Display class:

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Display;

use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IAssetResolver;

final class ExampleDisplay implements IDisplay {

	public function __construct(
		private readonly IMvcView $view,
		private readonly IAssetResolver $assetResolver
	) {}

	public static function getName(): string {
		return 'exampledisplay';
	}

	public function setData($data) {
		// optional
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$this->view->setPath(DIR_PLUGIN . 'ExamplePlugin');
		$this->view->setTemplate('Display/ExampleDisplay.php');
		$this->view->assign('title', 'Example');
		$this->view->assign('resolve', fn($src) => $this->assetResolver->resolve($src));

		return $this->view->loadTemplate();
	}

	public function getHelp(): string {
		return 'Shows the example display.';
	}
}
```

Template:

```php
<?php
	$containerId = 'example_' . uniqid();
?>
<div id="<?php echo $containerId; ?>">
	<h2><?php echo htmlspecialchars($this->_['title']); ?></h2>
</div>

<script>
	(function() {
		var scriptUrl = <?php echo json_encode($this->_['resolve']('plugin/ExamplePlugin/assets/example/example.js')); ?>;

		if (typeof AssetLoader !== 'undefined') {
			AssetLoader.loadScriptAsync(scriptUrl);
		}
	})();
</script>
```

Keep class and template paths parallel.

---

## 4. Add assets

Place files under:

```text
assets/
└── example/
    ├── example.css
    └── example.js
```

Reference them through logical plugin paths:

```text
plugin/ExamplePlugin/assets/example/example.js
```

Do not hardcode final public URLs.

The asset resolver decides how this logical path becomes a URL in the current project.

---

## 5. Add language files

Create:

```text
lang/
└── Administration/
    ├── de.ini
    └── en.ini
```

Example:

```ini
[administration]
base3_admin_tab_example = "Example"
base3_admin_subtab_exampledisplay = "Example Display"
```

Keep keys stable.

Use language files for UI labels, admin navigation, tabs, and reusable text.

---

## 6. Add a job

Create:

```text
src/Job/ExampleCleanupJob.php
```

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Job;

use Base3\Worker\Api\IJob;

final class ExampleCleanupJob implements IJob {

	public static function getName(): string {
		return 'examplecleanupjob';
	}

	public function isActive() {
		return true;
	}

	public function getPriority() {
		return 1;
	}

	public function go() {
		// cleanup logic
		return 'Example cleanup done.';
	}
}
```

The worker can discover it through:

```php
$classMap->getInstancesByInterface(IJob::class);
```

or run it by name:

```php
$classMap->getInstanceByInterfaceName(IJob::class, 'examplecleanupjob');
```

---

## 7. Add a policy-controlled job

Use a policy-controlled job when execution depends on a reusable policy.

Conceptual structure:

```php
final class ExampleSyncJob implements IPolicyControlledJob {

	use PolicyControlledJobTrait;

	public static function getName(): string {
		return 'examplesyncjob';
	}

	public function isActive() {
		return true;
	}

	public function getPriority() {
		return 1;
	}

	public function getPolicyDefinition(): array {
		return [
			'policy' => 'dailywindowjobpolicy',
			'data' => [
				'from' => '02:00',
				'to' => '04:00'
			]
		];
	}

	public function go() {
		// work
		$this->markRun();

		return 'Sync done.';
	}
}
```

The worker resolves the policy through the class map.

---

## 8. Add a dependency check

Create a class implementing `ICheck`.

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Check;

use Base3\Api\ICheck;
use Base3\Database\Api\IDatabase;

final class ExampleDatabaseCheck implements ICheck {

	public function __construct(
		private readonly IDatabase $database
	) {}

	public static function getName(): string {
		return 'exampledatabasecheck';
	}

	public function checkDependencies() {
		$this->database->connect();

		return [
			'example_database' => $this->database->connected() ? 'Ok' : 'not connected'
		];
	}
}
```

Diagnostic tools can discover it:

```php
$classMap->getInstancesByInterface(ICheck::class);
```

---

## 9. Add an event

Create an event class:

```text
src/Event/ExampleStartedEvent.php
```

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Event;

use Base3\Event\BaseEvent;

final class ExampleStartedEvent extends BaseEvent {

	public function __construct(
		private readonly string $id,
		private readonly string $timestamp = ''
	) {}

	public function getId(): string {
		return $this->id;
	}

	public function getTimestamp(): string {
		return $this->timestamp !== ''
			? $this->timestamp
			: (new \DateTimeImmutable())->format('c');
	}
}
```

Fire it from a service:

```php
$this->eventManager->fire(new ExampleStartedEvent($id));
```

---

## 10. Add an event listener

Create:

```text
src/Listener/ExampleEventListener.php
```

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Listener;

use ExamplePlugin\Event\ExampleStartedEvent;

final class ExampleEventListener {

	public function onStarted(ExampleStartedEvent $event): void {
		// react to event
	}
}
```

Register it in plugin `init()`:

```php
$listener = new ExampleEventListener();

$eventManager = $this->container->get(IEventManager::class);
$eventManager->on(ExampleStartedEvent::class, [$listener, 'onStarted']);
```

If the event manager may not yet exist, provide a fallback:

```php
$this->container->set(
	IEventManager::class,
	fn() => new EventManager(),
	IContainer::SHARED | IContainer::NOOVERWRITE
);
```

---

## 11. Add a hook listener

Create a class implementing `IHookListener`.

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Hook;

use Base3\Hook\IHookListener;

final class ExampleBootstrapHookListener implements IHookListener {

	public static function getSubscribedHooks(): array {
		return [
			'bootstrap.start' => 0
		];
	}

	public function isActive(): bool {
		return true;
	}

	public function handle(string $hookName, ...$args) {
		// react to hook
		return null;
	}
}
```

Hook listeners are discovered before plugin `init()`.

Therefore constructor dependencies must already be available early.

---

## 12. Add a config value mode

Create:

```text
src/ConfigValue/VaultConfigValueModeResolver.php
```

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\ConfigValue;

use Base3\ConfigValue\Api\IConfigValueModeResolver;
use RuntimeException;

final class VaultConfigValueModeResolver implements IConfigValueModeResolver {

	public static function getName(): string {
		return 'vaultconfigvaluemoderesolver';
	}

	public function getMode(): string {
		return 'vault';
	}

	public function supports(array|string|int|float|bool|null $config): bool {
		return is_array($config)
			&& ($config['mode'] ?? null) === 'vault'
			&& isset($config['key']);
	}

	public function resolve(array|string|int|float|bool|null $config): mixed {
		if (!is_array($config)) {
			throw new RuntimeException('Vault config value definition must be an array.');
		}

		$key = $config['key'] ?? null;

		if (!is_string($key) || $key === '') {
			throw new RuntimeException('Vault config value definition requires a non-empty key.');
		}

		return $this->readVaultValue($key);
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'key' => [
					'type' => 'string',
					'description' => 'Vault key.'
				]
			],
			'required' => ['key']
		];
	}

	private function readVaultValue(string $key): ?string {
		return null;
	}
}
```

The central config value resolver discovers mode resolvers through the class map.

Use this only for generic modes that are useful beyond one subsystem.

Domain-specific modes should stay in domain-specific resolvers.

---

## 13. Create a project plugin

A project plugin wires the final application.

It may depend on several normal plugins because it is the composition root.

Example:

```php
<?php declare(strict_types=1);

namespace ProjectPlugin;

use Base3\Api\IContainer;
use Base3\Api\IPlugin;
use Base3\Database\Api\IDatabase;
use Base3\Settings\Api\ISettingsStore;
use Base3\Settings\Database\DatabaseSettingsStore;
use ResourceFoundation\Api\IQuerySchemaProvider;
use Vizion\Api\IReportConfigProvider;

final class ProjectPlugin implements IPlugin {

	public function __construct(
		private readonly IContainer $container
	) {}

	public static function getName(): string {
		return 'projectplugin';
	}

	public function init() {
		$this->container
			->set(self::getName(), $this, IContainer::SHARED)

			->set(ISettingsStore::class, fn($c) => new DatabaseSettingsStore(
				$c->get(IDatabase::class)
			), IContainer::SHARED)

			->set(IQuerySchemaProvider::class, fn($c) => new ProjectQuerySchemaProvider(), IContainer::SHARED)

			->set(IReportConfigProvider::class, fn($c) => new ProjectReportConfigProvider(), IContainer::SHARED);
	}
}
```

This is the place where concrete project decisions belong.

---

## 14. Project plugin dependency rule

A project plugin may import normal plugin classes.

Reusable normal plugins should usually avoid doing that.

Recommended:

```text
NormalPlugin -> Base3 APIs
NormalPlugin -> FoundationPlugin APIs
ProjectPlugin -> NormalPlugin implementations
ProjectPlugin -> FoundationPlugin APIs
```

Allowed direct dependency:

```text
ExtensionPlugin -> Plugin it explicitly extends
```

If a plugin cannot make sense without another plugin, direct dependency is acceptable.

Document it clearly.

---

## 15. Use project-local configuration files

A project plugin may have:

```text
local/
├── Chatbot/
│   ├── default-agentflow.json
│   └── default-systemprompt.txt
├── Data/
│   └── schema.json
└── Vizion/
    └── reports.json
```

Use this for project-specific configuration, presets, prompts, schemas, and sample data.

Different projects can then use different local files while sharing the same reusable plugins.

Guideline:

```text
Reusable defaults belong to the reusable plugin.
Project-specific choices belong to the project plugin.
Runtime user data belongs to storage.
```

---

## 16. Create a new foundation area

A new foundation area is useful when several plugins need shared contracts but should not depend on each other directly.

Create:

```text
ExampleFoundation/
├── README.md
├── src/
│   ├── Api/
│   ├── Dto/
│   ├── Exception/
│   ├── Model/
│   ├── Proxy/
│   └── ExampleFoundationPlugin.php
├── test/
└── VERSION
```

Use it to define:

* interfaces
* DTOs
* shared models
* exceptions
* neutral proxies
* capability interfaces
* plugin slots

Do not put final project wiring there.

---

## 17. Foundation area example

Example foundation:

```text
SearchFoundation/
├── src/
│   ├── Api/
│   │   ├── ISearchService.php
│   │   ├── ISearchIndex.php
│   │   └── ISearchResultNormalizer.php
│   ├── Dto/
│   │   ├── SearchQuery.php
│   │   ├── SearchResult.php
│   │   └── SearchHit.php
│   ├── Exception/
│   │   ├── SearchUnavailableException.php
│   │   └── SearchValidationException.php
│   └── SearchFoundationPlugin.php
└── VERSION
```

A consumer plugin depends on:

```php
SearchFoundation\Api\ISearchService
```

An implementation plugin provides:

```php
DatabaseSearchService
RemoteSearchService
HybridSearchService
```

The project plugin wires:

```php
ISearchService::class => HybridSearchService
```

---

## 18. Foundation design checklist

Before creating a new foundation plugin, ask:

```text
Do multiple plugins need this contract?
Should implementations be replaceable?
Would direct dependencies create coupling?
Are the data structures shared?
Do consumers need common exception types?
Can this stay independent of one implementation?
```

If yes, a foundation plugin is appropriate.

If only one plugin uses the interfaces, keep them inside that plugin for now.

---

## 19. Foundation interface rules

A foundation interface should be:

* small
* stable
* implementation-neutral
* host-neutral
* easy to test
* explicit about return types
* explicit about exceptions
* free of project-specific assumptions

Good:

```php
interface IQueryService {

	public function query(QueryStatement $statement): QueryResult;
}
```

Less good:

```php
interface IEverythingService {

	public function handle(array $payload): mixed;
}
```

---

## 20. Foundation DTO rules

Use DTOs when several plugins exchange the same structured data.

Good DTOs are:

* typed
* documented
* serializable when possible
* not bound to a specific database schema
* not bound to a specific host system
* not filled with service dependencies

If the same array shape appears in several plugins, consider a DTO.

---

## 21. Foundation exception rules

Use foundation exceptions for stable error categories.

Examples:

```text
AccessDeniedException
ValidationException
StorageUnavailableException
UnsupportedCapabilityException
```

Do not put implementation-specific low-level exceptions into the foundation layer unless consumers need to catch them generically.

---

## 22. Add an implementation plugin for a foundation slot

If a foundation defines:

```php
IFileStorage
```

an implementation plugin may provide:

```php
LocalFileStorage
RemoteFileStorage
EncryptedFileStorage
```

It can register a fallback:

```php
$this->container->set(
	IFileStorage::class,
	fn($c) => new LocalFileStorage(DIR_LOCAL . 'files/'),
	IContainer::SHARED | IContainer::NOOVERWRITE
);
```

A project plugin may override this with a project-specific binding.

---

## 23. Add an extension plugin

An extension plugin intentionally extends another plugin.

Example:

```text
ReportingPlugin
ReportingChartsPlugin
```

The extension plugin may depend on the base plugin's API or classes.

That is acceptable when the extension has no meaningful standalone purpose.

Document this clearly in the plugin README.

---

## 24. Avoid unwanted plugin dependencies

Bad pattern:

```text
Plugin A imports concrete classes from Plugin B
Plugin B imports concrete classes from Plugin C
Plugin C imports concrete classes from Plugin A
```

Better:

```text
Shared contracts move to Foundation Plugin
Implementations stay separate
Project Plugin wires final bindings
```

This avoids circular dependencies and keeps replacement possible.

---

## 25. Typical extension decision table

```text
Need one known service?
  -> Register it in the container.

Need many implementations by interface?
  -> Use PluginClassMap.

Need one implementation by name?
  -> Use getInstanceByInterfaceName().

Need shared contracts across plugins?
  -> Create or use a Foundation Plugin.

Need final project choice?
  -> Wire it in a Project Plugin.

Need runtime notification?
  -> Use Events.

Need framework lifecycle extension?
  -> Use Hooks.

Need background work?
  -> Add a Job.

Need editable runtime settings?
  -> Use SettingsStore.

Need operational progress/state?
  -> Use StateStore.

Need value from env/file/config/fixed?
  -> Use ConfigValueResolver.
```

---

## 26. Common mistakes

### Wiring final implementations in foundation plugins

Foundation plugins define slots.

Project plugins choose implementations.

### Depending on normal plugins from reusable plugins

Use foundation interfaces instead.

### Creating factories for class map lookups

Use the class map directly unless runtime construction logic is needed.

### Hardcoding asset URLs

Use logical plugin asset paths and `IAssetResolver`.

### Putting request logic into plugin `init()`

Use displays, outputs, services, jobs, or middleware.

### Using settings as state

Settings are configuration datasets.

State is runtime status.

### Using project-local files as storage

Local files are fine for project presets.

Use storage for runtime data.

---

## 27. Add a database migration provider

Use a migration provider when a plugin or framework implementation owns database schema that may evolve over time.

Create:

```text
src/Migration/ExamplePluginMigrationProvider.php
src/Migration/Migration001CreateTables.php
src/Migration/Migration002AddIndexes.php
```

Provider example:

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
			Migration001CreateTables::class,
			Migration002AddIndexes::class
		];
	}
}
```

Migration step example:

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Migration;

use Base3\Database\Api\IDatabase;
use Base3\Migration\Api\IDatabaseMigration;

final class Migration001CreateTables implements IDatabaseMigration {

	public function __construct(
		private readonly IDatabase $database
	) {}

	public static function getName(): string {
		return 'exampleplugin_001_create_tables';
	}

	public function getVersion(): string {
		return '001';
	}

	public function getDescription(): string {
		return 'Creates the initial ExamplePlugin tables.';
	}

	public function up(): void {
		$this->database->connect();
		$this->database->nonQuery(
			'CREATE TABLE IF NOT EXISTS `example_items` (`id` INT NOT NULL AUTO_INCREMENT, `title` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
	}
}
```

Do not run this from plugin `init()`. The configured migration runner runs after all plugins have initialized.


---

## 28. Add configured component instances

Use this pattern when one discoverable implementation class should exist as multiple configured runtime instances.

Example use cases:

```text
- two retrieval tools using different vector databases
- several connector instances with different credentials
- skill/module instances with different instruction sets
```

Create an implementation that implements a component interface:

```php
<?php declare(strict_types=1);

namespace ExamplePlugin\Tool;

use Base3\Api\IComponent;
use Base3\Core\ComponentDefinition;

interface IExampleTool extends IComponent {

	public function name(): string;
}

final class RagTool implements IExampleTool {

	public function __construct(
		private readonly ComponentDefinition $definition,
	) {}

	public static function getName(): string {
		return 'rag';
	}

	public function id(): string {
		return $this->definition->id;
	}

	public function name(): string {
		return $this->definition->metadata['toolName'] ?? $this->definition->id;
	}
}
```

Register the definitions in plugin `init()`:

```php
use Base3\Api\IContainer;
use Base3\Core\ComponentDefinition;
use ExamplePlugin\Tool\IExampleTool;

$definition = new ComponentDefinition(
	id: 'internal-rag',
	interfaceName: IExampleTool::class,
	implementationName: 'rag',
	config: [
		'vector_db' => 'internal',
	],
	metadata: [
		'toolName' => 'rag_internal_search',
	]
);

$this->container->set(
	$definition->getServiceName(),
	$definition,
	IContainer::PARAMETER
);
```

Resolve the configured component at runtime:

```php
$tool = $componentResolver->get(IExampleTool::class, 'internal-rag');
```

The component resolver reads definitions from the existing container and delegates construction to the class map. It is not a second container.

---

## 29. Summary

BASE3 extension work follows a small set of patterns:

```text
Plugin = feature module.
Foundation Plugin = contract package.
Implementation Plugin = concrete behavior.
Project Plugin = final wiring.
PluginClassMap = discovery.
Container = active services and parameters.
ComponentResolver = configured component instances, registered as a normal core service.
```

The most important practical rule is:

```text
Do not let reusable plugins accidentally depend on concrete project choices.
```

Use foundation interfaces for shared contracts.

Use project plugins to decide which implementations are active.

That is how BASE3 keeps plugins replaceable.
