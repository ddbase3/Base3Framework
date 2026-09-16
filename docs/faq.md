# BASE3 Framework FAQ

## What is the BASE3 Framework?

BASE3 is a lightweight, modular PHP framework for extensible applications. The framework core provides bootstrap and runtime composition, a service container, dependency injection, class map discovery, routing, request handling, MVC support, plugins, hooks, events, workers, configuration, settings, state, database abstraction, sessions, authentication, and other technical foundation services.

BASE3 can run as a standalone application runtime or as an embedded subsystem inside another host system.

## Which PHP version is required?

The current README specifies PHP 8.1 or later as the runtime requirement.

## Is Composer required to run BASE3?

No. Composer is optional for the BASE3 runtime. The repository supports Composer for development tooling and for a project-wide dependency graph in which plugins can add their own Composer dependencies when needed.

## Does BASE3 require a database?

No. The framework is designed to run without a database. The default bootstrap, for example, uses `NoMigrationRunner` and does not require a concrete `IDatabase` implementation.

If a project needs database functionality, it can provide an appropriate `IDatabase` implementation and the services that depend on it as part of its runtime composition.

## Which databases are supported by the framework?

The framework contains implementations for MySQL or MariaDB and PostgreSQL. Not every database-backed subsystem is automatically equally suitable for both SQL dialects. The actual compatibility depends on the concrete implementation being used.

## What is the difference between the service container and the class map?

The service container is intended for known services and their active implementations. The class map is used to discover components by interface, application, and technical name.

Typical container services include `IConfiguration`, `IRequest`, and `IDatabase`. Typical class map components include plugins, outputs, jobs, checks, hook listeners, and migration providers.

## How are dependencies provided to runtime classes?

Runtime classes should receive known dependencies through constructor injection. The concrete implementation is selected during bootstrap, plugin composition, or project composition, not inside the consuming runtime class.

## What are plugins used for?

Plugins extend BASE3 with technical or domain-specific functionality. They can provide services, outputs, displays, jobs, checks, listeners, assets, templates, settings, and other discoverable components.

In the default layout, plugin classes under `plugin/<PluginName>/src` are discovered by `PluginClassMap`.

## What are foundation plugins?

Foundation plugins define stable shared contracts for replaceable implementations. They typically contain interfaces, DTOs, models, and exceptions while keeping concrete project logic to a minimum.

## What is a project plugin?

A project plugin is the composition layer for a concrete application. It selects and wires the final implementations for shared service contracts.

## How does routing work?

BASE3 can resolve outputs by their technical name. The framework includes both classic query-based selection and a route-based service selector variant. Pretty URLs can be mapped by the host or web server configuration.

## What is an `IOutput`?

An `IOutput` is a routable output component with a stable technical `getName()` and a `getOutput()` method. The service selector can resolve a matching implementation through the class map and execute it.

## What MVC support does BASE3 provide?

BASE3 provides a lightweight template layer through `IMvcView` and `MvcView`. An output or display class prepares data, assigns it to the view, and renders a PHP template from the corresponding template directory.

## How should assets be referenced?

Plugin assets should be resolved through `IAssetResolver`. This keeps the logical asset path inside a plugin separate from the public URL used by the deployment.

## How are incoming request data handled?

`IRequest` wraps the PHP input sources. The default implementation can expose GET, POST, COOKIE, SESSION, SERVER, and FILES data, parse JSON request bodies, and map supported CLI arguments into a GET-like structure.

## Does BASE3 start a session automatically?

Not in the default bootstrap. The framework provides multiple `ISession` implementations and `SessionMiddleware`. A project must explicitly select the desired session implementation and add the middleware to its runtime composition.

## Is authentication enabled by default?

No. The default bootstrap binds `IAccesscontrol` to `NoAccesscontrol`, so the framework core does not automatically enable user authentication.

BASE3 provides several authentication strategies that a project can select and combine deliberately.

## Does BASE3 provide users, roles, and permissions?

Yes. `IUsermanager` defines framework-level access to users, groups, roles, and permissions. A database-backed BASE3 system implementation is included. The active Usermanager implementation is selected by the project.

## What is the difference between Configuration, Settings, and State?

`IConfiguration` is intended for static or deployment-oriented configuration. `ISettingsStore` manages editable grouped and named settings datasets. `IStateStore` is intended for operational runtime state such as locks, cursors, or last-run markers.

These responsibilities should remain separate.

## Where can settings be stored?

The framework includes a JSON-backed Settings Store and a database-backed Settings Store. The JSON implementation uses a configured data path and stores its data in `cnf/settings.json` below that path. The database implementation stores settings datasets as JSON in a table.

## How does logging work?

BASE3 defines `ILogger` as its central logging interface. File and database implementations are included. The active logger, the amount of logged information, and retention are determined by the concrete application.

## Does BASE3 send telemetry or analytics data to external providers by default?

No external telemetry, analytics, or monitoring service is registered by the default bootstrap. BASE3 does include optional mechanisms for HTTP-based microservice communication. Data is transferred through those mechanisms only when an application explicitly configures and uses them.

## Does BASE3 support microservices?

Yes. The framework includes microservice endpoints and connectors for HTTP-based remote calls. Depending on configuration, method parameters, results, and optionally the current user identifier can be transferred between systems.

## How do background jobs work?

Jobs implement `IJob` and are discovered through the class map. Workers can prioritize and execute jobs. Discoverable execution policies are available for reusable execution conditions.

## How do hooks and events differ?

Hooks are mainly intended for extending defined framework lifecycle points. Events are intended for runtime and domain notifications. Both mechanisms reduce direct coupling between the source and its reactions, but they serve different responsibilities.

## Does BASE3 automatically run database migrations during startup?

The bootstrap calls the configured `IMigrationRunner`. By default this is `NoMigrationRunner`, which performs no migrations. A database-backed application can explicitly provide `DatabaseMigrationRunner`.

## Where can BASE3 store tokens?

The framework includes `FileToken`. This implementation stores token hashes and expiration timestamps in JSON files under `local/FileToken`. Use of this implementation is optional and depends on the application composition.

## What data can BASE3 process from a privacy perspective?

BASE3 is a framework and does not define the business data processing of a concrete application. Its APIs and optional implementations can, however, process request data, session data, user and permission information, configuration, settings, runtime state, logs, tokens, files, and microservice payloads.

Which of these data categories are actually processed or stored in a specific installation depends on the active runtime composition and the application built on top of it. Technical privacy notes are provided in [PRIVACY.md](../PRIVACY.md).

## Where can I find the technical documentation?

The curated framework documentation is located in `docs/`. Useful starting points include `overview.md`, `architecture-principles.md`, `bootstrap.md`, `dependency-injection.md`, `classmap.md`, `plugins.md`, `coding-conventions.md`, and `extension-cookbook.md`.

## Which license applies to BASE3?

The repository is licensed under GPL-3.0-or-later. The complete license terms are available in `LICENSE`.
