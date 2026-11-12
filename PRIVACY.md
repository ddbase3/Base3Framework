# BASE3 Framework: technical privacy and security inventory

Review: 2026-11-03. Source: supplied BASE3 framework implementation and
current `src/Usermanager` and authorization contracts. This technical record
is not an operator-specific privacy notice or legal compliance determination.

## Processing and data categories

BASE3 supplies reusable interfaces and implementations for identity,
authentication, authorization, sessions, settings, state, logs, database access,
and request handling. These components can process user and account IDs,
login names, roles, groups, permissions, session identifiers, request metadata
and application-provided content. Which concrete service is active is selected
by the host/project composition; the framework alone cannot establish the
identity of the controller or the legal basis for a given deployment.

The built-in code-owned `authenticated` and `anonymous` logical groups read
only the already trusted current user object (or its absence) through the
existing `Base3AuthenticatedCriterion` and `Base3AnonymousCriterion`. They
create no records, change no native roles and never authorize an action on
their own. In hosts that support logical group audiences, the single existing
`UserCriterionEvaluator` processes membership in memory. Framework-level
group definitions have no independent account store.

## Storage, deletion, retention and access

The interfaces do not mandate any physical backend. Concrete sessions,
settings, databases, file storage, logs and caches are supplied or configured
by the active host. This patch adds neither a table nor a file format nor a
retention job. Removing the built-in group definitions removes their computed
audience entries; it does not delete host-owned identities, feature rules,
logs, backups or any native rights. Those must be handled by the responsible
host through its existing deletion and access processes.

Exact retention durations, backup expiration and native account erasure
behavior are unknown from the supplied framework code alone and must be
established for each deployment, including data subject access, correction,
restriction and deletion workflows.

## Recipients, transfers, security and open verification

The new group definitions perform no network request and add no recipient.
Other framework services may use configured databases, proxies, remote
services or application endpoints; their deployment geography, subprocessors
and transfer mechanisms cannot be established from the supplied source.

Feature authorizations are decisions of the active `IAuthorizationService`.
Logical group membership is not an implicit native permission grant. The
active host owns authentication integrity, transport security, logging,
CSRF protection for mutations and resource-level authorization. A full
runtime privacy/security audit and accessibility review of the complete
host installation remain deployment obligations. The new group definitions
have no interactive UI of their own.

## FileBridge FileManager integration for chat.base3.de (2026-11-05)

The `Base3ChatWebsite` project binds FileBridge's `LocalManagedFileStorageService` under `[directories] data`/`filemanager/`. The configured data directory is independent of `DIR_ROOT` and must be an absolute path that is writable by PHP. The project FileManager output authorizes chatbot configuration operations using `IAuthorizationService`, user identity from `IUsermanager`, a fixed `chatbot/default` owner, and AJAX-only POST actions. File content and temporary upload metadata are stored locally, not transmitted to a new recipient by the file storage backend. Storage owner directories are hashed and separated. Session metadata includes the actor ID, owner, filename, path, size, chunk sizes, time and multipart upload IDs.

Completed and aborted uploads remove their session artifacts. Abandoned sessions may persist until manually cleaned, and backup retention, web-server logging and deployment filesystem permissions are not established by the provided project archive. The operator must deny direct HTTP access to the configured `[directories] data`/`filemanager` directory (Apache `.htaccess` alone is insufficient on other servers), define deletion/backup periods, and verify access controls on the deployed web server before public use. These are known operational gaps, not claims of finished regulatory compliance.

## Standalone global chat rendering (2026-11-05)

The `Base3ChatWebsite` project adds a global chatbot launcher to full HTML
website pages through `Base3Template`. ClientStack may store window-mode and
reopen preferences in browser local or session storage, as documented by the
ClientStack plugin. An explicitly allowed BASE3 feature is required to render
the launcher and separately to fetch HTML for the `chatbot/default` instance.
Conversations continue to use the existing Chatbot/Assistant services, whose
processor locations and retention depend on deployment configuration. The
Chatbot Configuration tab now presents the existing FileBridge-backed
FileManager for that same instance. The local data-directory storage and upload processing,
rights checks and known operational gaps remain documented in the site and
ClientStack privacy statements. No remote file storage service is introduced.


## Read-only source diagnostics (2026-11-12)

BASE3's structured diagnostics now include checks for module manifests,
module dependency versions, static PHP type references, migration signatures
and classmap source naming. The checks read installed module metadata and
PHP source files locally and produce an in-memory `DiagnosticCheckResult`
with bounded technical module identifiers and relative source locations.
They do not require or execute inspected plugin code, modify module files,
run database migrations, persist findings or contact external services.
The framework adds no diagnostic database, retention mechanism or recipient.

Diagnostic result access remains the responsibility of the existing
invoking endpoint and `IAuthorizationService`; the checks themselves grant
no rights. Technical source identifiers can disclose installation structure,
so diagnostics must not be exposed to unauthorized users. Web-server access
logs or host-provided monitoring may retain requests to the diagnostic UI;
those records and their retention are outside the checks and must be
verified for each deployment.
