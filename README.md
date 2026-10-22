# Base3Framework

Base3Framework is a lightweight yet powerful PHP framework designed to simplify modern web development. It offers a range of essential features that help developers build scalable, maintainable, and fast applications. The framework includes a variety of components such as a robust autoloader, a service container, dependency injection, microservices support, MVC architecture, plugin functionality, and extensive interfaces for extensibility.

This README will guide you through the installation and usage of the Base3Framework and highlight its unique features.

## Features

* **Autoloader**: Automatically loads classes as needed, eliminating the need to include files manually.
* **Service Container**: Central place for defining and retrieving services and parameters.
* **Dependency Injection**: Provides flexibility in managing object dependencies through inversion of control.
* **Microservices**: Create and consume microservices with support for DI, routing, flags, and receivers.
* **ClassMap**: Maps classes to specific locations and offers utilities for discovery and instantiation.
* **MVC Architecture**: Clean separation of concerns using controller, view, and model logic.
* **Plugin System**: Modular architecture for extending the framework with isolated plugins.
* **Multilingual**: Support for runtime language selection and language-specific views.
* **Event & Hook System**: Register listeners and dispatch events or hooks with prioritization.
* **Page Modules**: Pluggable page components (header, footer, content, etc.) with priorities and dependencies.
* **Worker & Cron System**: Register and schedule background jobs with priorities.
* **Token System**: Secure, scoped tokens for time-limited operations (e.g. password reset, validation).
* **Session & Authentication**: Manage session lifecycle and login/logout functionality.
* **Usermanager & RBAC**: Framework-level users, groups, roles, and permissions via `IUsermanager`.
* **Logging & Configuration**: Central services for storing logs and configuration data.

## Requirements

* PHP 8.1 or higher
* A web server (e.g., Apache or Nginx)

## Installation

### Step 1: Clone the repository

```bash
git clone https://github.com/ddbase3/Base3Framework.git
```

### Step 2: Configuration

Some settings might need to be adjusted to suit your environment. You can configure the application by using the example file in the `cnf` directory. Key settings include database connections, languages, and logging.

## Composer (optional)

Composer is optional for the BASE3 runtime, but the repository tooling can build one project-level dependency graph from `composer.base.json` and opted-in plugin Composer files.

A plugin participates in the merge when its `composer.json` contains:

```json
{
  "_base3": {
    "merge": true
  }
}
```

Generate the root `composer.json` with:

```bash
php tools/setup/merge-composer.php
```

or use the normal build target:

```bash
make install
```

The generated root `composer.json` is disposable. `composer.base.json` is the stable root input.

See `docs/developer-tooling.md` for dependency merging and build details.

## Using the Makefile

```bash
make install      # Merge Composer config, install dependencies, build plugin files
make update       # Merge Composer config, update dependencies, build plugin files
make rootfiles    # Copy plugin rootfiles into the project root
make publicfiles  # Copy plugin publicfiles into public/
make assets       # Copy plugin assets into public/assets/<PluginName>/
make test         # Run PHPUnit
make coverage     # Build docs/coverage
make stan         # Run PHPStan
make doc          # Build docs/phpdoc
make clean        # Remove vendor/ and generated composer.json
```

## Framework Components

### Autoloader

Efficient class loading based on namespaces and class maps.

### Service Container

Provides service registration and lookup. Supports flags like `SHARED`, `NOOVERWRITE`, `ALIAS`, and `PARAMETER`.

### Dependency Injection

Integrated via container or constructor-based injection.

Example:

```php
class UserService {
    private Database $database;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function getUserData(int $userId): array {
        return $this->database->fetchUser($userId);
    }
}
```

### Microservice Architecture

Use `IMicroservice`, `IMicroserviceConnector`, `IMicroserviceReceiver`, and `IMicroserviceFlags` for building structured microservice endpoints and consumers.

### MVC & Page System

Pages implement `IPage`, `IPageCatchall`, `IPagePostDataProcessor`. Views and modules use `IMvcView` and `IPageModule*` interfaces.

### Event & Hook System

* Use `IEventManager`, `IStoppableEvent`
* Hook-based extensibility via `IHookListener` and `IHookManager`

### Workers & Cron

Jobs implement `IJob`; cron-capable jobs use `ICron`. Workers implement `IWorker` and orchestrate job execution.

### Token System

Create, check, and delete scoped tokens using `IToken` (e.g. for secure links, confirmation flows).

### Session & Authentication

* `ISession` for checking session state
* `IAuthentication` for login/logout
* `IAccesscontrol` for user ID access

### Users, authorization and rights administration

`IUsermanager` exposes the current identity, groups, effective roles and permission
metadata. `searchUsers(string $query, int $limit = 20, int $offset = 0): array`
provides paginated native user-directory lookup by literal login, name, email or
exact numeric ID. Results contain `User` identities and do not grant access.
The backend searches without fetching the entire directory; the caller must
check permission before presenting personal data. A blank query returns `[]`.
Host implementations and remote connectors must implement the new method before
upgrading the shared interface. Only `IAuthorizationService` evaluates whether
an action may be executed:

```php
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Usermanager\Permission;

$allowed = $authorization->decide(new AuthorizationRequest(
    $authorization->getDefaultAuthority(),
    Permission::for('system', 'admin')
))->isAllowed();
```

Authorization requests can include native resource and subject references, trusted
context and action parameters. `IUserCriterion`, `IGroup` and `IFeature` are
`IClassMap`-discoverable extension slots. Project bindings choose a native backend;
BASE3 defines **no new authorization database**, hierarchy engine or parallel
`IUsermanager::can()` method. See `docs/authorization-model.md`,
`docs/authorization-adapters.md` and `docs/usermanager.md`.

### Language System

Use `ILanguage` to manage and switch between available languages at runtime.

### Logging

Log entries using `ILogger`, with support for scoped log streams.

### Configuration

Manage structured configuration data using `IConfiguration`.

## Example Usage

### Hello World Example

1. Create a Controller:

```php
use Base3\Page\Api\IPage;

class HelloController implements IPage {
    public static function getName(): string {
        return 'hello';
    }

    public function getUrl() {
        return '/hello.html';
    }

    public function getOutput(string $out = 'html', bool $final = false): string {
        return '<h1>Hello, World!</h1>';
    }
}
```

2. Place the class below a source tree scanned by the active `IClassMap`.

3. Request it through the active routing style, for example `?name=hello&out=html` or the configured pretty URL `/hello.html`.

## Developer Documentation

The curated framework documentation lives in `docs/`.

Frequently asked questions: [docs/faq.md](docs/faq.md)

Privacy and data processing notes: [PRIVACY.md](PRIVACY.md)

Start with:

```text
docs/overview.md
docs/architecture-principles.md
docs/bootstrap.md
docs/dependency-injection.md
docs/classmap.md
docs/plugins.md
docs/base3-manifest.md
docs/coding-conventions.md
docs/foundation-plugins.md
docs/components.md
docs/extension-cookbook.md
```

Subsystem guides include:

```text
docs/routing.md
docs/pages.md
docs/mvc.md
docs/middlewares.md
docs/accesscontrol-authentication.md
docs/sessions.md
docs/language-translation.md
docs/microservices.md
docs/configuration.md
docs/configvalue.md
docs/settingsstore.md
docs/statestore.md
docs/databases.md
docs/migrations.md
docs/logging.md
docs/hooks.md
docs/events.md
docs/worker.md
docs/assets.md
docs/tokens.md
docs/cryptography.md
docs/cache.md
docs/knowledge.md
docs/service-registry.md
docs/core-contracts.md
docs/checks-diagnostics.md
docs/request-data.md
docs/systemservice.md
docs/usermanager.md
docs/developer-tooling.md
docs/utilities.md
```

Generated PHPDoc and coverage output are separate from the curated Markdown guides.

---

## 🔌 Available BASE3 Plugins

The BASE3 Framework can be extended through numerous plugins that add modular functionality, visualizations, management tools, and AI-based features. Below is a selection of useful extensions:

### 📊 Data & Visualization

- **[DataHawk](https://github.com/ddbase3/DataHawk)**  
  Interactive reports with configurable tables and charts directly within BASE3 pages.
- **[Vizion](https://github.com/ddbase3/Vizion)**  
  Visual representation of data using bar, pie, line charts, etc., based on DataHawk.
- **[Memora](https://github.com/ddbase3/Memora)**  
  CRM/XRM service for knowledge graphs; optimized for AI usage, i.e. Graph RAG.

### 🤖 AI & Automation

- **[Chatbot](https://github.com/ddbase3/Chatbot)**  
  Chatbot UI for integration anywhere in a webproject, i.e. as a page module or a bubble.
- **[MissionBay](https://github.com/ddbase3/MissionBay)**  
  Agent-based system for orchestrating autonomous tasks, event-driven communication, and workflows.

### ⚙️ Management & Infrastructure

- **[Base3Manager](https://github.com/ddbase3/Base3Manager)**  
  UI for managing and activating BASE3 plugins.
- **[ManageWebsite](https://github.com/ddbase3/ManageWebsite)**, **[ManageDatabase](https://github.com/ddbase3/ManageDatabase)**, **[ManageGit](https://github.com/ddbase3/ManageGit)**, **[ManageServer](https://github.com/ddbase3/ManageServer)**, **[ManageIlias](https://github.com/ddbase3/ManageIlias)**, and more 
  Specialized manager plugins for system or project-level administration.

### 🛠️ Development Tools

- **[Base3Tools](https://github.com/ddbase3/Base3Tools)**  
  Collection of useful development tools for working with BASE3.
- **[Debugger](https://github.com/ddbase3/Debugger)**, **[IliasDebugger](https://github.com/ddbase3/IliasDebugger)**  
  Advanced debugging plugins – also suitable for ILIAS-based projects.

### 🧩 ILIAS Integration
- **[Base3Ilias](https://github.com/ddbase3/Base3Ilias)**  
  BASE3 ILIAS Bridge for DI container connections
- **[Base3IliasAdapter](https://github.com/ddbase3/Base3IliasAdapter)**  
  ILIAS UI hook plugin - extensions for deep integration with ILIAS.
- **[ManageBase3Ilias](https://github.com/ddbase3/ManageBase3Ilias)**  
  BASE3 Manager plugin for managing and configure BASE3 components within ILIAS environments.

### 📦 Other Plugins

- **[ModuledPage](https://github.com/ddbase3/ModuledPage)**  
  Modular page elements for structured content presentation.
- **[FreeTemplate](https://github.com/ddbase3/FreeTemplate)**  
  Starter template for custom BASE3 plugins (LGPL).
- **[OpenAiConversation](https://github.com/ddbase3/OpenAiConversation)**  
  Flow-based chatbot with OpenAI integration, suitable for RAG-based applications.

> You can find more plugins at: [github.com/topics/base3-plugin](https://github.com/topics/base3-plugin)

## Contributing

Contributions are welcome. Follow `docs/coding-conventions.md` and document new public interfaces clearly.

## Example Projects Using BASE3

* [Mosaic Creator](https://mosaic-creator.de)
* [Contourz Ballet Photography](https://contourz.photo)

## License

Base3Framework is licensed under the GPL 3.0 License. See the LICENSE file for more details.

## Generated API Documentation

Run:

```bash
make doc
```

to generate PHPDoc under `docs/phpdoc/`. The curated developer guides remain the Markdown files listed above.

## Links

* [Official project page (documentation)](https://base3.de/crm.php?id=05fdf4d623714e24a7418c160795ef34)



## Authorization audiences

BASE3 defines discoverable `IGroup`, `IUserCriterion`, and `IFeature` contracts.
Both host-owned group membership and feature eligibility use one read-only
`UserCriterionEvaluator` for composable criteria; feature audiences additionally
accept effective BASE3 group references. See
[Authorization Model](docs/authorization-model.md) and
[Authorization Adapters](docs/authorization-adapters.md).

<!-- BASE3 XRM authorization migration 2026-10-19 -->

See [XRM and Manager authorization](docs/authorization-xrm-manager.md) for the native authorization roles, updated client/server endpoints, and deployment instructions.


### Structured diagnostics

Discoverable `Base3\Diagnostics\Api\IDiagnosticCheck` and `IDiagnosticScope` implementations provide read-only checks, categorized scopes and translatable structured `DiagnosticCheckResult` values. `IDiagnosticRunner` discovers implementations through `IClassMap` and runs one check per invocation. The old `ICheck` API remains available unchanged until a separate migration. See `docs/checks-diagnostics.md`.

### Feature-based diagnostic access

`Base3\Diagnostics\Feature\DiagnosticReportsFeature` is discoverable as an `IFeature`. Its permission is `feature/diagnosticreportsfeature`, with the logical BASE3 `development` group as its default audience. Feature audience decisions belong to `IAuthorizationService` and may be customized through the host's existing feature administration. The diagnostic UI and its AJAX execution must both enforce the permission, independently of menu visibility.
