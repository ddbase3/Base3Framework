# BASE3 Framework Plugins

## Purpose

This document explains how **plugins** work in the BASE3 framework.

It is written for developers who want to understand:

* what a BASE3 plugin is
* where plugins are located
* how plugins are discovered
* how `PluginClassMap` scans plugin classes
* what `IPlugin` is for
* what happens inside a plugin `init()` method
* how plugins register services
* how plugins expose outputs, displays, jobs, checks, migration providers, listeners, policies, and other extension classes
* how plugin assets, templates, language files, tests, local data, and documentation are structured
* how MVC-style display classes and templates are kept parallel
* how embedded systems can use another plugin layout by providing another class map

After reading this document, a developer should understand how to create, structure, and initialize a BASE3 plugin.

---

## 1. What a plugin is

A BASE3 plugin is a self-contained extension package.

It can add:

* services
* outputs
* displays
* controllers
* templates
* assets
* language files
* jobs
* checks
* migration providers
* event listeners
* hook listeners
* config value modes
* migration providers
* settings UIs
* domain APIs
* factories
* adapters
* resources
* tests
* documentation

A plugin is not only a folder with code.

It is a discoverable application module that participates in the BASE3 runtime through:

```text id="hyy4gn"
PluginClassMap
IPlugin
IContainer
IBase::getName()
extension interfaces
```

The important idea is:

```text id="s4yjdq"
A plugin becomes active because its classes are discoverable and its plugin class initializes services.
```

---

## 2. Typical standalone project structure

In a typical standalone BASE3 installation, the framework root contains a `plugin` directory.

Conceptual structure:

```text id="od6rh5"
Base3Framework/
├── cnf/
├── docs/
├── index.php
├── local/
├── plugin/
├── src/
├── test/
├── tmp/
├── tpl/
├── userfiles/
├── vendor/
└── VERSION
```

The `plugin` directory contains one directory per plugin:

```text id="u1nqee"
plugin/
├── AssistantFoundation/
├── Chatbot/
├── ClientStack/
├── DataHawk/
├── MissionBay/
├── PrivacyVault/
├── ResourceFoundation/
├── UiFoundation/
├── Vizion/
└── ...
```

Each plugin directory is an application area with its own source code and optional supporting files.

---

## 3. Plugin discovery

In the default standalone bootstrap, BASE3 registers:

```php id="zxluro"
PluginClassMap
```

as the implementation of:

```php id="rgl9o2"
IClassMap
```

The plugin-aware class map scans:

```text id="jpvnlr"
DIR_SRC
DIR_PLUGIN/<PluginName>/src
```

That means plugin PHP classes are discovered when they live under:

```text id="vo61ko"
plugin/<PluginName>/src
```

This is why the `src` directory is the most important directory inside a plugin.

Classes outside `src` are not automatically discovered by the default plugin class map.

---

## 4. Embedded or host-specific layouts

The standalone layout is the normal BASE3 project layout.

However, BASE3 can also be embedded into another system.

In embedded setups, the directory structure may differ.

Examples in abstract terms:

```text id="tz5zzs"
host-system/
├── components/
│   └── base3/
│       ├── framework/
│       └── plugins/
└── public/
```

or:

```text id="nbyf02"
application/
├── modules/
├── base3/
├── extensions/
└── runtime/
```

When the plugin directory differs from the default `DIR_PLUGIN` layout, the integration should provide a custom class map or custom bootstrap that scans the correct plugin locations.

The rest of the framework should still depend on:

```php id="uu13t8"
IClassMap
```

not on one concrete directory layout.

---

## 5. Typical plugin structure

A plugin commonly contains:

```text id="g4e1xf"
ExamplePlugin/
├── assets/
├── docs/
├── install/
├── lang/
├── LICENSE
├── local/
├── README.md
├── src/
├── test/
├── tpl/
└── VERSION
```

Not every plugin needs every directory.

A small plugin may only contain:

```text id="ujk6p1"
ExamplePlugin/
├── src/
├── tpl/
└── VERSION
```

A larger plugin may contain documentation, assets, tests, local sample data, install scripts, and many source subdomains.

---

## 6. Required and optional plugin directories

## 6.1 `src/`

Contains PHP classes.

This is the directory scanned by `PluginClassMap`.

Typical contents:

```text id="s6qep5"
src/
├── Api/
├── Content/
├── Display/
├── Event/
├── Job/
├── Migration/
├── Listener/
├── Service/
└── ExamplePlugin.php
```

Classes that should be discovered by the class map must live here.

---

## 6.2 `tpl/`

Contains PHP templates.

Typical structure mirrors controller or display classes:

```text id="l76z76"
tpl/
├── Content/
│   └── ChatbotDisplay.php
└── Display/
    └── ConnectionConfigDisplay.php
```

Templates are not discovered as classes.

They are loaded explicitly by view services.

---

## 6.3 `assets/`

Contains static frontend files.

Examples:

```text id="u2ar9k"
assets/
├── css/
├── js/
├── icons/
├── images/
└── vendor/
```

Plugin assets may include:

* JavaScript
* CSS
* SVG icons
* images
* bundled frontend libraries
* browser-side modules

Assets should be referenced through the framework's asset resolution mechanism instead of hardcoded absolute paths where possible.

---

## 6.4 `lang/`

Contains language files.

Example structure:

```text id="oc9b0r"
lang/
└── Administration/
    ├── de.ini
    └── en.ini
```

Language files are useful for:

* admin tabs
* labels
* subtab names
* display names
* UI text
* plugin-specific translations

A language file can group keys by section:

```ini id="qwtkhc"
[administration]
base3_admin_tab_provider = "Provider"
base3_admin_subtab_connectionconfigdisplay = "Connections"
base3_admin_subtab_agentadmindisplay = "Agents"
```

Keep translation keys stable because they are often referenced by admin navigation, templates, or display definitions.

---

## 6.5 `test/`

Contains plugin tests.

Typical structure mirrors `src/`:

```text id="tr8273"
test/
├── Service/
│   └── ExampleServiceTest.php
├── Content/
│   └── ExampleDisplayTest.php
└── ExamplePluginTest.php
```

Tests should verify:

* service behavior
* plugin initialization
* output rendering
* factories
* exporters
* validators
* class map discoverability where useful

---

## 6.6 `docs/` or `doc/`

Contains plugin-specific documentation.

Examples:

```text id="m2xph5"
docs/
├── architecture.md
├── sequence.md
└── usage.md
```

or:

```text id="wl3v8b"
doc/
├── notes.txt
├── example.txt
└── schema.json
```

Use this for plugin-specific architecture, examples, schemas, prompts, or operational notes.

---

## 6.7 `install/`

Contains install or migration instructions.

Example:

```text id="m4y8vv"
install/
└── install.txt
```

This is useful when a plugin needs:

* database tables when no automatic migration provider exists
* initial settings
* external setup steps
* manual migration notes

---

## 6.8 `local/`

Contains plugin-local sample or runtime files when the plugin needs them.

Example:

```text id="wtjzw5"
local/
├── Example/
│   └── sample.json
└── Data/
    └── default-profile.json
```

Be careful with `local/`.

Large runtime data should usually live in proper data storage, not in plugin source packages.

---

## 6.9 `VERSION`

Contains the plugin version.

Example:

```text id="p1jggy"
1.0.0
```

This allows plugin tooling, diagnostics, or admin screens to show plugin versions.

---

## 6.10 `README.md` and `LICENSE`

These document the plugin and its license.

They are not used by the class map, but they are important for maintainability and distribution.

---

## 7. Source directory conventions

The `src/` directory usually contains subdirectories by role.

Common examples:

```text id="x21zos"
src/
├── Api/
├── Agent/
├── Compiler/
├── Connection/
├── Content/
├── Display/
├── Dto/
├── Event/
├── Export/
├── Factory/
├── Job/
├── Migration/
├── Listener/
├── Memory/
├── Model/
├── Node/
├── Policy/
├── Repository/
├── Resource/
├── Service/
├── Transport/
├── Util/
└── ExamplePlugin.php
```

There is no requirement that every plugin use all of these.

Use directories that match the plugin's domain.

---

## 8. Plugin class

Each plugin should normally have one main plugin class implementing:

```php id="ogk7s7"
Base3\Api\IPlugin
```

Example:

```php id="ycqlcv"
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
			ExampleService::class,
			fn() => new ExampleService(),
			IContainer::SHARED
		);
	}
}
```

The plugin class is discovered by `PluginClassMap`.

The bootstrap resolves all `IPlugin` implementations and calls:

```php id="z8j6o4"
$plugin->init();
```

---

## 9. The `IPlugin` contract

The plugin interface is:

```php id="upxlqp"
<?php declare(strict_types=1);

namespace Base3\Api;

interface IPlugin {

	public function __construct(IContainer $container);

	public function init();

}
```

This means a plugin receives the shared container during construction.

The plugin then uses `init()` to register services and perform setup.

---

## 10. What `init()` is for

The plugin `init()` method is the plugin composition point.

Typical responsibilities:

* register plugin services
* register service aliases
* register default implementations
* register event manager or config resolver if not already present
* attach event listeners
* define plugin-specific factories
* register repositories
* register adapters
* wire services together
* expose the plugin object itself in the container if useful

Example:

```php id="wguccr"
public function init() {
	$this->container
		->set(self::getName(), $this, IContainer::SHARED)

		->set(IExampleService::class, fn($c) => new ExampleService(
			$c->get(ISettingsStore::class),
			$c->get(IClassMap::class)
		), IContainer::SHARED);
}
```

`init()` should not normally execute request logic.

Request logic belongs in outputs, displays, controllers, jobs, or services.

---

## 11. Registering services

A plugin registers services in the container.

Example:

```php id="ibv96k"
$this->container->set(
	IExampleRepository::class,
	fn($c) => new ExampleRepository($c->get(IDatabase::class)),
	IContainer::SHARED
);
```

Use interface bindings when other code should depend on the abstraction:

```php id="xzo2se"
IExampleRepository::class
```

Use concrete class bindings only when the concrete class is intentionally the service identity.

---

## 12. `NOOVERWRITE` service registration

Some plugins register shared infrastructure only if no other implementation exists.

Conceptual example:

```php id="fncrjr"
$this->container->set(
	IEventManager::class,
	fn() => new EventManager(),
	IContainer::SHARED | IContainer::NOOVERWRITE
);
```

This pattern is useful when:

* a plugin needs the service
* the framework or host may already provide it
* another plugin may provide a stronger implementation
* the plugin wants to provide a fallback, not force replacement

Use `NOOVERWRITE` for shared cross-plugin infrastructure when replacing an existing binding would be wrong.

---

## 13. Plugin initialization order

The default bootstrap flow is:

```text id="h8iigd"
1. create container
2. register core services
3. register PluginClassMap as IClassMap
4. discover hook listeners
5. dispatch bootstrap.init
6. discover plugins
7. call plugin.init()
8. dispatch bootstrap.start
9. run service selector
```

Important:

The plugin class must be instantiable before its own `init()` method runs.

Therefore the constructor of the plugin class should usually depend only on early services.

The safest dependency is:

```php id="suvdbi"
IContainer
```

because the plugin can then resolve services inside `init()` when needed.

---

## 14. Plugin class naming and namespace

A plugin named:

```text id="nm1yfd"
ExamplePlugin
```

usually has this class:

```text id="yzl921"
plugin/ExamplePlugin/src/ExamplePlugin.php
```

with namespace:

```php id="uqxq37"
namespace ExamplePlugin;
```

and class:

```php id="tq2f80"
final class ExamplePlugin implements IPlugin
```

The class map derives the expected namespace from the plugin directory and path below `src`.

So this file:

```text id="qi25tp"
plugin/ExamplePlugin/src/Service/ExampleService.php
```

should define:

```php id="qa2bg8"
ExamplePlugin\Service\ExampleService
```

---

## 15. Discoverable plugin components

A plugin can add discoverable classes by implementing extension interfaces.

Examples:

```php id="ncl4s7"
IPlugin
IOutput
IDisplay
IHookListener
IJob
ICheck
IJobExecutionPolicy
IConfigValueModeResolver
```

The class map can then find them by interface:

```php id="lwihgq"
$classMap->getInstancesByInterface(ICheck::class);
```

or by interface and logical name:

```php id="x6w7ck"
$classMap->getInstanceByInterfaceName(
	IOutput::class,
	'exampledisplay'
);
```

A discoverable component should usually implement `IBase` through its extension interface and provide a stable `getName()`.

---

## 16. Plugin services versus discoverable classes

Not every plugin class needs to be discovered by name.

There are two main categories.

### Registered services

Registered services are known dependencies.

Examples:

```text id="vtdj9m"
IExampleService
IExampleRepository
IExampleFactory
```

They are registered in `init()` and consumed through constructor injection.

### Discoverable components

Discoverable components are selected by interface, name, or app.

Examples:

```text id="d5cl8e"
outputs
displays
jobs
checks
policies
config value modes
hook listeners
```

They are found by the class map.

Practical rule:

```text id="ts12rj"
Container for known shared services.
PluginClassMap for discoverable plugin components.
```

---

## 17. MVC-style display structure

A common BASE3 plugin pattern is a parallel structure between display/controller classes and templates.

Example:

```text id="ovog9b"
src/Display/DataSchemaDisplay.php
tpl/Display/DataSchemaDisplay.php
```

or:

```text id="cm7on7"
src/Content/ChatbotDisplay.php
tpl/Content/ChatbotDisplay.php
```

The PHP class prepares data.

The template renders markup.

This keeps business logic and HTML output separated.

---

## 18. Example display class with template

A display class typically receives an MVC view service.

```php id="pkytm7"
<?php declare(strict_types=1);

namespace ExamplePlugin\Display;

use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IAssetResolver;

final class DataSchemaDisplay implements IDisplay {

	public function __construct(
		private readonly IMvcView $view,
		private readonly IAssetResolver $assetResolver
	) {}

	public static function getName(): string {
		return 'dataschemadisplay';
	}

	public function setData($data) {
		// store display data
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$data = [
			'items' => []
		];

		$this->view->setPath(DIR_PLUGIN . 'ExamplePlugin');
		$this->view->setTemplate('Display/DataSchemaDisplay.php');
		$this->view->assign('data', $data);
		$this->view->assign('resolve', fn($src) => $this->assetResolver->resolve($src));

		return $this->view->loadTemplate();
	}

	public function getHelp(): string {
		return 'Shows a schema display.';
	}
}
```

The class controls:

* data preparation
* template selection
* assigned template variables
* asset resolution helpers

The template controls:

* HTML markup
* small rendering logic
* script bootstrapping
* style blocks when appropriate

---

## 19. Example template

A matching template could look like this:

```php id="gsqf4w"
<?php
	$containerId = 'dataschema_' . uniqid();
?>
<div id="<?php echo $containerId; ?>" class="dataschema"></div>

<script>
	(function() {
		var containerId = <?php echo json_encode($containerId); ?>;
		var data = <?php echo json_encode($this->_['data']); ?>;
		var scriptUrl = <?php echo json_encode($this->_['resolve']('plugin/ExamplePlugin/assets/schema/schema.js')); ?>;

		async function boot() {
			await AssetLoader.loadScriptAsync(scriptUrl);
			document.getElementById(containerId).dataset.ready = '1';
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', boot, { once: true });
		} else {
			boot();
		}
	})();
</script>
```

This pattern keeps the asset path inside a resolver call instead of hardcoding deployment-specific URLs.

---

## 20. Assets

Plugin assets usually live under:

```text id="jsqbwy"
plugin/<PluginName>/assets
```

Examples:

```text id="nnzsi2"
assets/
├── chatbot/
│   ├── chatbot.css
│   └── chatbot.js
├── icons/
│   ├── send.svg
│   └── reload.svg
└── vendor/
    └── library.min.js
```

Use assets for:

* JavaScript behavior
* plugin CSS
* icons
* frontend libraries
* images
* browser-only modules

In templates, assets should usually be resolved through an asset resolver:

```php id="c7xk6s"
$this->_['resolve']('plugin/ExamplePlugin/assets/widget/widget.js')
```

This keeps templates portable across standalone and embedded deployments.

---

## 21. Language files

Language files can be stored under:

```text id="adzwoi"
lang/
```

A common pattern is:

```text id="kp1mzu"
lang/
└── Administration/
    ├── de.ini
    └── en.ini
```

Example:

```ini id="zadjg5"
[administration]
base3_admin_tab_provider = "Provider"
base3_admin_subtab_connectionconfigdisplay = "Connections"
base3_admin_subtab_agentadmindisplay = "Agents"
```

Language keys should be stable.

Recommended naming style:

```text id="kg0j3q"
base3_admin_tab_<domain>
base3_admin_subtab_<displayname>
```

Keep display class names and subtab keys aligned where useful.

---

## 22. Plugin tests

Plugin tests typically live in:

```text id="qyi2ej"
test/
```

They often mirror `src/`.

Example:

```text id="p4jf79"
test/
├── Service/
│   └── ExampleServiceTest.php
├── Display/
│   └── ExampleDisplayTest.php
└── ExamplePluginTest.php
```

A plugin should test:

* service registration
* important services
* factories
* validators
* displays
* exporters
* jobs
* domain logic
* integration-specific fallback behavior

---

## 23. Plugin documentation

A plugin can include docs under:

```text id="lvditb"
docs/
```

or:

```text id="lzv34m"
doc/
```

Examples:

```text id="kefli3"
docs/
├── architecture.md
├── sequence.md
└── usage.md
```

Use plugin docs for information that does not belong in the framework docs:

* domain architecture
* plugin-specific setup
* special configuration
* expected data formats
* integration notes
* examples
* migrations

---

## 24. Plugin-local data

Some plugins include local example files, prompts, schemas, or development data.

Examples:

```text id="grmabf"
local/
├── Chatbot/
│   └── example-systemprompt.txt
└── Data/
    └── example-schema.json
```

or:

```text id="cjemp0"
ai/
├── example-flow.json
├── example-node.txt
└── agentnodes.json
```

This is useful for development examples or packaged presets.

Do not use plugin-local files as a replacement for proper runtime storage when the data is large, user-specific, or frequently changed.

---

## 25. Plugin install files

A plugin may include:

```text id="xkg5h2"
install/
└── install.txt
```

or other install/migration notes.

Use this for:

* database setup
* manual migration instructions
* initial settings
* required external services
* operational setup notes

---

## 26. Plugin version

A plugin can include:

```text id="y6a4cv"
VERSION
```

with a simple version string:

```text id="ay34lp"
1.0.0
```

This can be used by:

* diagnostics
* admin screens
* update tools
* compatibility checks
* deployment scripts

---

## 27. Events in plugins

A plugin can use the event system to publish runtime behavior and register listeners.

Inside `init()`:

```php id="krv9vq"
$this->container->set(
	IEventManager::class,
	fn() => new EventManager(),
	IContainer::SHARED | IContainer::NOOVERWRITE
);

$listener = new ExampleEventDisplayListener(
	$this->container->get(IDatabase::class)
);

$eventManager = $this->container->get(IEventManager::class);

$eventManager->on(ExampleStartedEvent::class, [$listener, 'onStarted']);
$eventManager->on(ExampleFinishedEvent::class, [$listener, 'onFinished']);
$eventManager->on(ExampleFailedEvent::class, [$listener, 'onFailed']);
```

This pattern is useful when a plugin owns:

* event classes
* listener classes
* display or log projections
* runtime operations that should be observable

---

## 28. Checks in plugins

A plugin can implement:

```php id="n3gmhs"
ICheck
```

to expose dependency diagnostics.

Example:

```php id="o1t3tj"
public function checkDependencies() {
	return [
		'example_service_available' => $this->container->get(IExampleService::class) ? 'Ok' : 'missing'
	];
}
```

Diagnostic tools can discover all checks through the class map:

```php id="bw48et"
$checks = $classMap->getInstancesByInterface(ICheck::class);
```

This lets plugins publish health information without a central registry.

---

## 29. Jobs in plugins

A plugin can provide worker jobs.

Example structure:

```text id="ykewpk"
src/
└── Job/
    └── ExampleCleanupJob.php
```

A job class implements the worker job interface and returns a stable name.

The worker can discover plugin jobs through:

```php id="ax57f6"
$classMap->getInstancesByInterface(IJob::class);
```

and execute one job by name through:

```php id="gbs9a9"
$classMap->getInstanceByInterfaceName(IJob::class, 'examplecleanupjob');
```

This allows plugins to add background behavior without changing the worker core.

---

## 30. Migrations in plugins

A plugin that owns database schema should provide migration classes under its own `src/` tree.

Typical structure:

```text
plugin/ExamplePlugin/
└── src/
    └── Migration/
        ├── ExamplePluginMigrationProvider.php
        ├── Migration001CreateTables.php
        ├── Migration002AddIndexes.php
        └── Migration003BackfillDefaults.php
```

The provider implements `IDatabaseMigrationProvider` and is discovered through the class map. It should only be active when the plugin feature that owns the database schema is active in the current project.

Do not execute migrations inside `init()`. `init()` registers services. The configured migration runner executes migrations after all plugins have initialized and before request handling starts.

---

## 31. Config value modes in plugins

A plugin can add a new generic config value mode by implementing:

```php id="r5lxf7"
IConfigValueModeResolver
```

Example structure:

```text id="n3ycn9"
src/
└── ConfigValue/
    └── VaultConfigValueModeResolver.php
```

The central config value resolver can discover mode resolvers through:

```php id="eas64f"
$classMap->getInstancesByInterface(IConfigValueModeResolver::class);
```

This is a common extension pattern:

```text id="adqrgl"
plugin class adds the service
class map discovers the mode resolver
runtime code consumes IConfigValueResolver
```

---

## 32. Outputs and displays in plugins

Plugins commonly expose UI or response components.

Example:

```text id="qnqdkh"
src/
├── Content/
│   └── ChatbotDisplay.php
└── Display/
    └── ConnectionConfigDisplay.php

tpl/
├── Content/
│   └── ChatbotDisplay.php
└── Display/
    └── ConnectionConfigDisplay.php
```

A display or output class should:

* implement the relevant interface
* provide a stable `getName()`
* receive dependencies through constructor injection
* prepare data
* select a template
* return rendered output

The corresponding template should:

* render markup
* use assigned variables
* resolve assets through helpers
* avoid business logic

---

## 33. APIs inside plugins

A plugin can define its own interfaces under:

```text id="xvvaef"
src/Api/
```

Example:

```text id="hg1lzf"
src/Api/
├── IExampleService.php
├── IExampleRepository.php
└── IExampleDriverDefinition.php
```

Plugin APIs make it easier for:

* other plugins to depend on abstractions
* services to be replaced
* tests to use fakes
* class map discovery to target stable extension points

When another plugin should integrate with your plugin, expose a small and stable API interface.

---

## 34. Service registration style

Prefer this style:

```php id="a58o9a"
$this->container->set(
	IExampleService::class,
	fn($c) => new ExampleService(
		$c->get(ISettingsStore::class),
		$c->get(IClassMap::class)
	),
	IContainer::SHARED
);
```

Avoid hiding many dependencies inside a service locator call in the service itself.

Good runtime class:

```php id="sdoiey"
public function __construct(
	private readonly ISettingsStore $settingsStore,
	private readonly IClassMap $classMap
) {}
```

Less good:

```php id="xpx7au"
public function __construct(
	private readonly IContainer $container
) {}
```

Use the container directly mainly in plugin `init()` and bootstrap composition code.

---

## 35. Plugin dependencies

A plugin can depend on services or other plugins.

When doing so, document the dependency.

Possible dependency styles:

* require another plugin to register a service
* require a specific interface binding
* check for availability through `ICheck`
* use `NOOVERWRITE` for optional fallback services
* fail early when a required service is missing

Example dependency check:

```php id="j2hu2t"
public function checkDependencies() {
	return [
		'example_dependency_available' => $this->container->has(IExampleDependency::class)
			? 'Ok'
			: 'IExampleDependency not registered'
	];
}
```

If the container does not support `has()` in the local API style, use the framework's available container inspection method.

---

## 36. Plugin initialization best practices

A plugin `init()` should be predictable.

Good `init()` behavior:

* register services
* register aliases
* attach event listeners
* register fallbacks with `NOOVERWRITE`
* expose plugin object if useful
* keep side effects small

Avoid:

* executing long-running logic
* running imports
* making external HTTP calls
* processing user requests directly
* writing large amounts of data
* depending on request-specific state unless necessary

Request-specific behavior should happen later in outputs, displays, controllers, middleware, jobs, or services.

---

## 37. Class map and plugin cache

Because plugin classes are discovered by the class map, new classes may not appear until the class map cache is regenerated.

During development, if a new plugin class is not found, check:

* is the file under `plugin/<PluginName>/src`?
* does the namespace match the path?
* does the class implement the expected interface?
* does the class implement `IBase` if name lookup is needed?
* is `DIR_TMP/classmap.php` stale?
* can the class be instantiated with current container services?

Then regenerate the class map.

Conceptually:

```php id="wyzzj0"
$classMap->generate(true);
```

---

## 38. Embedded plugin layouts

In a standalone framework, the default layout is:

```text id="vi5no9"
DIR_PLUGIN/<PluginName>/src
```

In an embedded system, plugins may live somewhere else.

In that case, a custom class map can scan different targets.

Example:

```php id="ef014i"
protected function getScanTargets(): array {
	return [
		[
			'basedir' => DIR_SRC,
			'subdir' => '',
			'subns' => 'Base3'
		],
		[
			'basedir' => DIR_HOST_PLUGINS,
			'subdir' => 'src',
			'subns' => ''
		]
	];
}
```

The plugin contract does not require the physical directory to be exactly the standalone directory.

It requires the active class map to find the plugin classes.

---

## 39. Good minimal plugin

A minimal plugin can look like this:

```text id="tf2rnm"
plugin/
└── ExamplePlugin/
    ├── src/
    │   ├── ExamplePlugin.php
    │   └── Content/
    │       └── ExampleDisplay.php
    ├── tpl/
    │   └── Content/
    │       └── ExampleDisplay.php
    └── VERSION
```

`ExamplePlugin.php` registers services.

`ExampleDisplay.php` provides an output/display.

The template renders the display.

`VERSION` identifies the plugin version.

---

## 40. Larger plugin structure

A larger plugin can look like this:

```text id="zmmom6"
ExamplePlugin/
├── assets/
│   ├── widget/
│   │   ├── widget.css
│   │   └── widget.js
│   └── icons/
├── docs/
├── install/
├── lang/
│   └── Administration/
│       ├── de.ini
│       └── en.ini
├── local/
├── README.md
├── src/
│   ├── Api/
│   ├── Content/
│   ├── Display/
│   ├── Event/
│   ├── Job/
│   ├── Listener/
│   ├── Service/
│   └── ExamplePlugin.php
├── test/
│   ├── Content/
│   ├── Service/
│   └── ExamplePluginTest.php
├── tpl/
│   ├── Content/
│   └── Display/
└── VERSION
```

This structure keeps different concerns separate while staying discoverable.

---

## 41. Common mistakes

### Plugin class outside `src/`

The default plugin class map scans:

```text id="vmyaw6"
plugin/<PluginName>/src
```

A plugin class outside this directory will not be discovered by default.

### Namespace does not match path

This file:

```text id="zq9yp2"
plugin/ExamplePlugin/src/Service/ExampleService.php
```

should define:

```php id="yxwu8g"
ExamplePlugin\Service\ExampleService
```

### Missing `IPlugin`

A plugin class that does not implement `IPlugin` will not be initialized as a plugin.

### Missing service registration

A class can be discoverable but still fail to instantiate if its constructor dependencies are not registered.

### Too much logic in `init()`

`init()` should compose services, not run the application.

### Hardcoded asset paths

Templates should resolve asset paths instead of assuming a fixed URL.

### Duplicated template and class names drifting apart

When using MVC-style display classes, keep:

```text id="o6i9k8"
src/Display/FooDisplay.php
tpl/Display/FooDisplay.php
```

aligned.

### Using plugin-local files as runtime database

Large or frequently changing runtime data should not be stored as plugin source files.

Use database, state store, settings store, or other appropriate storage.

---

## 42. Practical rules

Put plugin classes under `plugin/<PluginName>/src`.

Use namespace paths that match the file structure.

Create one main plugin class implementing `IPlugin`.

Use plugin `init()` to register services and listeners.

Use constructor injection for runtime classes.

Use the container for known services.

Use the class map for discoverable plugin components.

Use `NOOVERWRITE` when providing fallback infrastructure.

Keep display/controller classes and templates parallel.

Resolve assets through an asset resolver.

Keep language keys stable.

Keep plugin tests parallel to source structure.

Document plugin-specific setup inside the plugin.

Use a custom class map when embedded layout differs from the standalone plugin directory.

---

## 43. Summary

BASE3 plugins are the main extension mechanism for framework applications.

In the standard standalone layout, plugins live under:

```text id="o101fo"
plugin/<PluginName>
```

and discoverable PHP classes live under:

```text id="qq9a2u"
plugin/<PluginName>/src
```

The default `PluginClassMap` scans these plugin source directories and makes classes available by:

```text id="edufpe"
app
interface
name
```

A plugin becomes active when its main class implements `IPlugin`, is discovered by the class map, and its `init()` method is called by the bootstrap.

A plugin may include source code, templates, assets, language files, tests, documentation, local sample data, install notes, and a version file.

The main development pattern is:

```text id="kby3eh"
src/ for PHP classes
tpl/ for templates
assets/ for frontend files
lang/ for translations
test/ for tests
PluginClassMap for discovery
IPlugin::init() for service registration
```

In embedded systems, the physical layout may differ. In that case, the active class map must scan the correct plugin locations.

In short:

```text id="vru99p"
Plugins extend BASE3.
PluginClassMap discovers them.
IPlugin initializes them.
The container wires their services.
Templates and assets render their UI.
```
