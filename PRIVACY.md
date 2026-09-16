# Privacy and Data Processing in the BASE3 Framework

> This document describes the technical data processing capabilities of the BASE3 Framework component. It is not a legal privacy notice for a specific application or installation. The data actually processed depends on the active runtime composition, enabled plugins, configuration, and the application built on BASE3.

## 1. Scope

The BASE3 Framework is a technical runtime and extension platform. It provides bootstrap and runtime composition, request abstraction, a service container, dependency injection, class map discovery, routing, MVC support, plugins, sessions, authentication, user and authorization abstractions, configuration, settings, state, logging, database access, tokens, cache, workers, and microservice communication.

The framework therefore defines technical processing paths, but it does not define one uniform business purpose for data processing. The controller or other responsible party, legal basis, retention periods, data subject rights, and concrete recipients must be determined for each application or installation.

## 2. Privacy-relevant properties of the default bootstrap

The supplied default bootstrap registers services including `IRequest`, `IConfiguration`, `IClassMap`, `IComponentResolver`, `IHookManager`, `IMigrationRunner`, `IAccesscontrol`, and `IServiceSelector`.

Important default properties include:

- `IAccesscontrol` uses `NoAccesscontrol` by default.
- `IMigrationRunner` uses `NoMigrationRunner` by default.
- A database is not required for the framework to start.
- No session implementation is bound automatically by the default bootstrap.
- No Usermanager is bound automatically by the default bootstrap.
- No framework logger is bound automatically by the default bootstrap.
- No external telemetry or analytics service is registered by the default bootstrap.

Additional processing occurs only when an application enables corresponding services, middleware, plugins, or implementations.

## 3. Incoming request data

The default `Base3\Core\Request` implementation copies data from PHP input sources into the framework request abstraction. These sources can include:

- GET parameters,
- POST data,
- cookies,
- existing session data,
- server and request metadata from `$_SERVER`,
- upload metadata from `$_FILES`,
- JSON request bodies,
- supported command-line arguments during CLI execution.

Depending on the request, these sources can contain personal data. Server data can, for example, include network and client metadata. Uploads can contain personal content. The framework itself does not decide which business parameters an application accepts or how long an application retains them.

`IRequest` is initially an access layer for data belonging to the current request. Access through `IRequest` alone does not create persistent storage.

## 4. Sessions and cookies

BASE3 defines `ISession` and provides multiple session implementations. PHP-based implementations store runtime values in the active PHP session. The concrete server-side storage and lifetime of session data depend on the PHP and project configuration.

`SessionAuth` can use the current user identifier and additional authentication information in the session. The database-backed BASE3 Usermanager implementation can additionally cache the user object, groups, roles, and permissions below the session area `authentication`.

The framework also contains `CookieAuth`. When this authentication strategy is selected, it can set a cookie named `authentication`. The cookie contains a user identifier and a token. The built-in implementation uses a validity period of seven days.

Whether session or authentication cookies are actually used depends on the project composition. Cookie domain, lifetime, transport security, SameSite behavior, and other cookie properties must be reviewed and documented for the concrete application.

## 5. Authentication and access control

BASE3 provides `IAccesscontrol` and multiple `IAuthentication` strategies. These include session, cookie, internal HMAC, single sign-on, and other authentication mechanisms.

Depending on the selected implementation, these mechanisms can process technical or personal identifiers. The default bootstrap does not enable a concrete user login mechanism and instead uses `NoAccesscontrol`.

For a production application, at least the following should be documented:

- the active authentication strategy,
- the identifiers being processed,
- cookies or session values that are created,
- any external identity or host systems involved,
- logout, expiration, and revocation behavior.

## 6. Users, groups, roles, and permissions

`IUsermanager` defines a framework contract for user and RBAC data. When explicitly wired by a project, the supplied `Base3SystemUsermanager` implementation can read data from BASE3 system tables and modify assignments.

The data structures supported by this implementation include, among other fields:

- internal user ID,
- user identifier,
- display name,
- email address,
- language identifier,
- groups,
- roles,
- permissions,
- assignments between users, groups, roles, and permissions.

This implementation is not enabled automatically by the default bootstrap. If it is used, the concrete application must define the storage location, retention, maintenance, and deletion rules for user and authorization data.

## 7. Configuration, settings, and runtime state

BASE3 separates several storage purposes.

### 7.1 Configuration

`IConfiguration` is intended for static or deployment-oriented configuration. The default bootstrap uses `ConfigFile`, which reads `cnf/config.ini` by default. Configuration can contain technical credentials or other sensitive values if an application stores them there.

A database-backed `DatabaseConfiguration` implementation is also available.

Configuration files and database tables must be protected against unauthorized access according to the sensitivity of their actual contents.

### 7.2 Settings Store

`ISettingsStore` stores grouped and named settings datasets. Depending on the application, these datasets can contain personal or confidential information.

The supplied JSON implementation stores data in `cnf/settings.json` below a configured data directory. The database implementation stores the settings arrays as JSON in `base3_settingsstore`.

### 7.3 State Store

`IStateStore` is intended for operational runtime state such as locks, cursors, and last-run markers. `DatabaseStateStore` stores keys and JSON-encoded values with an optional expiration timestamp in a database table.

State values should contain only the data required for their technical purpose. Personal data should be stored there only when necessary for the concrete operational purpose and when that use is documented.

## 8. Logging and error logs

BASE3 provides `ILogger` together with file-backed and database-backed logger implementations. Log messages are determined by the calling application. Logs can therefore contain personal data if such data is explicitly written into a log message.

The built-in `FileLogger` stores log files under `local/FileLogger`. Database loggers store log text and technical metadata in database tables.

Separately, the supplied `index.php` configures PHP error logging to `tmp/php-error.log`. The content written there depends on occurring PHP errors and the runtime configuration.

For production installations, at least the following should be defined:

- active log levels and scopes,
- whether user identifiers, request data, network addresses, or business content are logged,
- who can access logs,
- how logs are rotated and deleted,
- whether error output to end users is disabled.

Complete request payloads, passwords, tokens, and other secrets should not be logged without a specific and documented operational purpose.

## 9. Databases

`IDatabase` abstracts database access. The framework contains concrete MySQL and PostgreSQL implementations. The tables and data that actually exist depend on the active services and plugins.

Several framework services have optional database-backed implementations, including Configuration, Settings Store, State Store, Logging, and Usermanager. The framework core itself does not require a database.

Database security, infrastructure-level encryption, backups, replication, administrative access, and retention periods are outside the generic framework contracts and must be defined in the concrete operating environment.

## 10. Tokens

BASE3 defines `IToken` and includes the file-backed `FileToken` implementation.

`FileToken` does not store the returned plaintext token. It stores a SHA-1 hash of the token together with an expiration timestamp. The files are stored under `local/FileToken`. The filename contains the sanitized scope and an MD5 value derived from the corresponding ID.

Expired entries are removed by this implementation during certain token operations. A concrete deployment should review the token purposes in use, their lifetimes, and when associated files are cleaned up.

## 11. Cache and local files

The framework contains a `FileCache` implementation which, when used, can retrieve external content and store it under `userfiles/cache/`. Metadata for these cache entries is managed through a database.

The consuming application decides which content is cached. If source content contains personal data, the corresponding protection, access control, retention, and deletion requirements also apply to the cached copy.

The framework core also contains the directories `local/`, `tmp/`, and `userfiles/` as technical storage locations. Any additional data stored there is determined by the concrete application and active components.

## 12. Microservice communication and external transfers

BASE3 contains an optional microservice system for HTTP-based remote calls. When used, an `AbstractMicroserviceConnector` transfers the method name and JSON-encoded method parameters to a configured endpoint.

Technical headers are used for internal transport authentication. When the connector is not operated with `INTERNALONLY`, the currently authenticated user identifier can additionally be forwarded to the remote BASE3 endpoint in the `auth` header.

Microservice communication is not enabled automatically by the default bootstrap. Once an application uses remote endpoints, at least the following should be documented:

- target system and operator,
- transferred method parameters and returned data,
- possible user identifiers,
- transport encryption,
- retention and logging on the remote side,
- roles and responsibilities of the systems involved.

## 13. External telemetry and analytics

The default bootstrap does not register an external telemetry or analytics service. The framework does not send request data to an external analytics provider solely as a result of its default initialization.

Explicitly configured functions such as microservice connectors or application-specific plugins are separate from this default behavior. Their data flows must be documented for the corresponding installation.

## 14. Workers, jobs, hooks, and events

Workers, jobs, hooks, and events define execution and notification mechanisms. They do not automatically store a specific category of personal data. The actual job, listener, or service implementation being executed can, however, read, modify, transfer, or log data.

Privacy requirements must therefore be evaluated at the actual business or technical implementation boundary, not only at the generic worker, hook, or event mechanism.

## 15. Data minimization and access protection

From a technical perspective, applications built on BASE3 should in particular:

- store only the request, session, settings, state, and log data required for their purpose,
- keep secrets out of logs and publicly accessible files,
- apply appropriate file permissions to `cnf/`, `local/`, `tmp/`, and other data directories,
- restrict database permissions to the required scope,
- enforce user and authorization checks at the responsible service or data access boundary,
- perform external transfers only through deliberately configured integrations,
- define lifecycles for temporary data, cache data, tokens, and logs.

The framework cannot make these decisions uniformly for every application built on top of it.

## 16. Retention and deletion

BASE3 does not define one global retention period for all data categories. Lifecycles differ by storage type and selected implementation.

A production application should review at least the following areas:

- PHP sessions and session files or alternative session backends,
- authentication cookies,
- user, group, role, and permission data,
- configuration and settings data,
- state entries and TTL values,
- file and database logs,
- PHP error logs,
- token files,
- cache files and cache metadata,
- uploaded or application-specific files,
- data on remote microservice systems,
- database backups and file backups.

Deletion of business data must be implemented by the application or the backend service responsible for that data. The generic framework does not provide an automatic, complete user-data or privacy deletion workflow.

## 17. Production readiness checklist

Before operating an application based on BASE3 in production, at least the following should be documented and reviewed for the concrete installation:

- active bootstrap and plugin composition,
- active database and persistence services,
- active session and authentication mechanisms,
- cookies in use and their properties,
- active Usermanager and authorization implementation,
- stored settings and state datasets,
- logging and error logging configuration,
- token and cache usage,
- upload and file storage locations,
- external microservice or other network connections,
- recipients and data categories of external transfers,
- technical and organizational access rights,
- retention and deletion periods,
- backup and restore procedures,
- procedures for access, correction, export, and deletion where the concrete application stores personal data.

## 18. Component boundary

This document describes only the technical capabilities and supplied implementations of the BASE3 Framework. Plugins, host systems, and applications can add further data processing and should document those processing activities separately for their own components.
