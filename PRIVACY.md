# BASE3 Framework Privacy and Data Processing

> **Technical transparency document for the BASE3 framework repository.** This file MUST remain in the repository root and MUST be updated with every code change that alters data processing, storage, retention, deletion, access, logging, or external communication.
>
> **Documentation review:** 2026-10-14. **Evidence status:** architecture and interfaces were available for this draft; the complete implementation repository, its effective runtime configuration, concrete host deployments, and provider contracts were not supplied for a code-level inventory. Sections marked **VERIFY AGAINST SOURCE** are unresolved and MUST be completed before claiming a comprehensive source-backed inventory.
>
> This document describes technical behavior and verification requirements. It is **not** a legal privacy notice under GDPR Articles 12 to 14, a controller/processor agreement, a record of processing activities, or a statement that any particular deployment is compliant.

## 1. Scope

BASE3 is a modular PHP framework that can run standalone or be embedded in other PHP applications.
The framework provides service interfaces and infrastructure for configuration, settings, state, request handling, routing, users, plugins, class-map discovery, assets, logging, events, hooks, migrations, and workers.
Concrete storage and processing depend on the selected implementations and the host environment.
Feature and foundation plugins may contribute additional data processing; they MUST publish their own source-backed privacy documentation where appropriate.

See [privacy and accessibility engineering policy](docs/privacy-accessibility.md) for binding development rules, statutory references, and the complete review workflow.

## 2. Boundaries and responsibility

BASE3 alone does not determine the legal controller, processor, lawful basis, or retention obligations of every installation.
The host application or deployment operator determines the processing purposes and selects final services.
A project plugin or custom bootstrap can replace database, settings, state, user, session, logging, and other implementations.
A third-party service can act as a recipient or processor depending on contracts and actual data flow.

**Operator documentation required:** identify the controller, processor roles, relevant contracts, privacy notice, applicable legal basis and rights contacts for the real installation.

## 3. Available source and verification boundary

This draft is grounded in project architecture documentation and the uploaded `framework-api.zip` contracts.
It does NOT establish what every concrete framework implementation stores or how long it retains it.
It does NOT establish which plugins, background jobs, loggers, remote providers, or host features are enabled in production.
It does NOT import MissionBay-specific tables, audit behavior, or provider choices into BASE3.

Before release, the framework repository owner MUST inspect actual implementation sources, migration steps, data schemas, and tests, then replace all material unresolved entries with verified statements and evidence.

## 4. Potential personal data categories

Depending on runtime composition, BASE3 and integrations may process:

- authenticated identity and user-related metadata;
- users, groups, roles, permissions, and access-control context;
- session identifiers, request parameters, headers, IP addresses, and browser metadata;
- messages, emails, names, and other application-defined values carried in services or events;
- configuration and settings that embed identifiers, endpoints, or credentials;
- state keys and values containing user- or tenant-scoped identifiers;
- log, audit, diagnostic, timing, and error metadata;
- files, attachments, or external service payloads transmitted through plugins;
- derived or pseudonymous values that remain linkable to individuals.

This is a risk-based list of **possible** categories, not a claim that every item is collected or persisted by default.

## 5. Typical technical flows

The general framework architecture supports this conceptual flow:

```text
HTTP/CLI/host request
  -> active bootstrap and project composition
  -> request / identity / access boundary
  -> selected application output, controller, service or worker
  -> injected settings, state, session, persistence or other backends
  -> optional plugin hooks, events, logging and external services
  -> response, persistent state and/or asynchronous follow-up
```

This illustrates possible boundaries, not a trace of every real request.
A concrete installation MUST document which branches actually execute and which fields they process.

## 6. Runtime-composition inventory

| Architecture area | Possible data handling | Verified persistence? | Required follow-up |
| --- | --- | --- | --- |
| `IRequest` / routing / middleware | Request identifiers, parameters, cookies, headers | Not established here | Review concrete request and server/host logs |
| `IUsermanager` | Identity, group membership, roles, permissions | Implementation dependent | Inspect active user backend, tables and retention |
| `ISettingsStore` | Grouped editable settings, possibly secrets or identifiers | Backend dependent | Enumerate groups, fields and masking behavior |
| `IStateStore` | Locks, cursors, operational markers and optional payloads | Backend dependent | Inspect key prefixes, TTL and deletion semantics |
| `IConfiguration` | Static/deployment values; possible paths/secrets | Backend dependent | Review config files, environment and access |
| `ConfigValueResolver` | Resolution of values including credentials | Depends on mode | Review secret handling and diagnostic exposure |
| Logger infrastructure | Exceptions, operational identifiers, messages | Backend dependent | Identify sink, field filtering, rotation and expiry |
| Session services | Session-scoped user or app data | Host/implementation dependent | Inspect session backend, lifetime and invalidation |
| Workers / jobs | Operational data, jobs, logs and possible external calls | Job dependent | Enumerate active jobs and their datasets |
| Migrations / database | Database schemas and domain/backend tables | Optional | Inspect actual migrations and on-demand tables |
| Hooks / events | Data in emitted payloads and listeners | Listener dependent | Inventory listeners and side effects |
| Assets / browser | Web requests, remote assets, client-side storage | Host/template dependent | Inspect bundled assets, external URLs and scripts |
| Feature plugins | Domain-specific data, caches and external processing | Plugin dependent | See each plugin's root `PRIVACY.md` |

**VERIFY AGAINST SOURCE:** Replace each "implementation dependent" entry with source-backed details for every shipped default implementation and active deployment configuration.

## 7. User data and RBAC

`IUsermanager` is a stable framework API; a concrete implementation may use local persistence or host-managed identity.
Groups and roles can express access relationships, and permissions express capabilities.
Identifiers and role assignment histories may be personal data.

`IUsermanager::searchUsers()` enables limited native user-directory searches by
login, name, email and exact numeric ID. The supplied free-text query and
`limit`/`offset` are passed to the active native backend; the framework's
`Base3SystemUsermanager` searches existing user records in place, returns only
identity-oriented `User` objects and does not persist searches or expand roles
per result. `UsermanagerProxy` forwards searches to its configured connector,
which may involve network transfer depending on deployment; no search-result
cache is introduced. The caller is responsible for authorization before
exposing personal data. Searchability, visible accounts, access logs, network
recipients and retention depend on the active host/backend and infrastructure.

Before describing actual collection or retention, inspect the active implementation and any host adapter.
Do not assume that changing a UI role automatically deletes corresponding user identity records or audit events.
Do not assume an access-control interface itself enforces every resource-specific ACL.

### Authorization interfaces (2026-10-19)

The framework now exposes the immutable `AuthorizationRequest`, `AuthorizationDecision`,
`SubjectReference`, and `ResourceReference` DTOs and the
`IAuthorizationService`/`IAuthorizationAdministrationService` contracts. These
objects may contain user identifiers, resource identifiers, native operation names and
trusted execution parameters. The framework's `AuthorizationMultiplexer` only
selects a registered authority, without storing decisions or building user profiles.
`UserCriterionEvaluator` evaluates host-provided user attributes in memory and holds
no persistent group-membership store. The framework has no new authorization database;
retention, native logs, access rules and erasure depend on the concrete host binding.
The optional `Base3SystemAuthorizationService` uses the existing usermanager's global
permission metadata, not a new permission table. Operators must verify native
implementations and their privacy notices separately; the contracts do not establish
GDPR compliance or actual deployment retention periods.

## 8. Settings

`ISettingsStore` handles editable grouped runtime settings.
Settings may include provider endpoints, service configuration, identity references, or credentials depending on the consumer.
The current storage implementation must be identified separately.
For secret-bearing settings, document masking, access, backup retention, rotation, and deletion.
**VERIFY AGAINST SOURCE:** list actual shipped default settings groups and locations.

## 9. Operational state

`IStateStore` handles operational runtime state such as locks, cursors, and last-run markers.
State values may be personal data if they contain account or session identifiers or cached results.
Expiry depends on actual backend and caller behavior; do not assert an automatic TTL universally.
**VERIFY AGAINST SOURCE:** list shipped key prefixes, types, expiration handling, and disposal.

## 10. Configuration and secrets

`IConfiguration` holds static/deployment configuration.
`ConfigValueResolver` resolves values using configured modes.
Possible secrets include credentials, API keys, file references, and environment variable values.
Resolved secrets MUST be protected from client responses, error traces, and ordinary log messages.
**VERIFY AGAINST SOURCE:** identify secrets held by default implementations and their redaction behavior.

## 11. Sessions and request processing

The host and selected runtime services determine session persistence and transport cookie behavior.
Session cookies and browser storage can trigger ePrivacy/national terminal-access requirements depending on purpose and necessity.
Request URLs and headers can be recorded independently by reverse proxies, hosts, and APM infrastructure.
**VERIFY AGAINST SOURCE/DEPLOYMENT:** describe concrete cookie names, purposes, lifetimes, session stores, and host access logs.

## 12. Logging, diagnostics, and errors

Logging may expose user identifiers, resource IDs, IP addresses, parameter values, exception messages, or confidential technical details.
The installed logger implementation and infrastructure determine storage, access, and retention.
Operational logs MUST be minimised and restricted; secrets must not be logged.
**VERIFY AGAINST SOURCE:** list actual logged fields, sinks, retention and any cleanup jobs.

## 13. Hooks, events, and workers

Hooks support framework lifecycle extension; events communicate runtime domain notifications; jobs support background execution.
These mechanisms are not inherently persistent, but listeners and jobs may write records or transmit data.
**VERIFY AGAINST SOURCE:** list sensitive event payloads and enabled listeners/jobs with data handling consequences.

## 14. Databases, schemas, and migrations

BASE3 is designed to run without a database.
The default bootstrap concept uses a no-op migration runner, while project composition may enable database migrations.
Each schema belongs to its feature/backend owner, not to the project plugin merely because it selects the implementation.
**VERIFY AGAINST SOURCE:** list exact tables, columns, indexes, retention rules, cascades and cleanup mechanisms for each shipped persistence implementation.

## 15. File, object, and cache storage

Feature and host implementations may persist files, cached values, or derived data.
Deleting a primary database row does not by itself prove all files, cache copies, vector points, or external objects are removed.
**VERIFY AGAINST SOURCE:** list backends, object keys, namespaces, cache TTLs and propagation paths.

## 16. External services and international transfers

The framework's generic interfaces cannot establish which third-party providers receive personal data.
A plugin may send data through HTTP, APIs, MCP, model providers, parsers, email, or other communication backends.
For each active external service the operator MUST document recipient, categories, purposes, region, contract, retention, subprocessors, and relevant transfer mechanism.
EU regional hosting alone does not establish that all processing and access remain in the EEA.
**VERIFY AGAINST DEPLOYMENT:** list active recipients and contract/transfer evidence.

## 17. Security and authorisation

The required safeguards depend on the configured environment and risks.
Controls may include authenticated transport, least-privilege service accounts, per-tenant authorisation, encryption, secret redaction, request-integrity protection, and secured administrator access.
Source and deployment review MUST confirm actual protections rather than presume they exist.
**VERIFY AGAINST SOURCE/DEPLOYMENT:** enumerate default mechanisms and required host protections.

## 18. Retention inventory

| Data category | Owner | Retention start | Duration/criterion | Cleanup implementation | Status |
| --- | --- | --- | --- | --- | --- |
| User/role/group records | Active user backend | To verify | To verify | To verify | Unverified |
| Session data | Active session/host backend | To verify | To verify | To verify | Unverified |
| Settings | Active settings backend | To verify | To verify | To verify | Unverified |
| State entries | Active state backend | To verify | To verify | To verify | Unverified |
| Logs | Active logger/host | To verify | To verify | To verify | Unverified |
| Persistent feature records | Owning plugin/backend | To verify | To verify | To verify | Unverified |
| Backups | Deployment infrastructure | To verify | To verify | To verify | Unverified |

**Release constraint:** Where a newly introduced feature stores personal data, its actual row MUST be completed before release. This table is not evidence of compliant retention.

## 19. Deletion and lifecycle

The right to erasure under GDPR Article 17 is not unconditional; the controller must assess the applicable conditions and exceptions.
Where deletion is required, the responsible backend should remove or restrict records as appropriate and propagate the result to relevant derived copies.
The concrete operation must distinguish logical from physical deletion.
Backups and recipient-controlled records require separate lifecycle analysis.

**VERIFY AGAINST SOURCE:** document the actual methods, jobs and cascades for each owned dataset.

## 20. Data-subject access, correction and export

A concrete operator is responsible for identity verification, rights decisions, response times, and necessary disclosures.
BASE3 owners must provide technical routes to identify, retrieve, correct, export, restrict or erase their data as legally required.
An `IUsermanager` interface alone does not prove that the selected user backend implements every privacy-right operation.
**VERIFY AGAINST SOURCE:** document supported operations and any operator workflow dependencies.

## 21. Backups and restoration

Document the actual backup providers and snapshot schedules.
Document which categories are backed up, how they are secured, when copies expire, and how deletions are re-applied on restore.
A live deletion MUST NOT be described as deletion from immutable backups without verification.
**VERIFY AGAINST DEPLOYMENT:** provide actual retention and restore process.

## 22. Optional features and default processing

The class map may discover capabilities that are not active in a deployment.
Optional implementation plugins are not evidence that their network calls or storage are enabled.
Document default-enabled and opt-in behavior separately.
Re-evaluate inventory when the project plugin changes selected implementations.

## 23. Privacy and accessibility overlap

Privacy settings, consent screens, data export, account deletion, and privacy notices must themselves be accessible.
All BASE3-owned interactive UI follows WCAG 2.2 AA as an internal engineering baseline, with additional legal standards where applicable.
An inaccessible privacy or rights-control interface can prevent users from exercising their rights effectively.
See [privacy and accessibility policy](docs/privacy-accessibility.md).

## 24. Operator-specific facts not determined by this document

The following MUST be completed by the actual deployment operator:

- controller, processor, DPO, supervisory authority and contact details where applicable;
- purpose and legal basis per processing activity;
- appropriate Article 9 grounds for special-category processing where applicable;
- actual data categories, data subjects, and enabled backend implementations;
- physical storage locations and storage providers;
- recipients, subprocessors, international transfers and safeguards;
- actual retention periods, cleanup ownership and deletion procedures;
- available rights-request routes and response procedures;
- audit/logging systems, security controls, incidents and breach workflow;
- cookies and other terminal-equipment storage/access behavior;
- statutory accessibility scope and operator accessibility statement where required.

## 25. Known gaps and mandatory next verification

At the current documentation evidence boundary, there is **no verified full framework storage/table inventory**.
There is **no verified complete set of default logger/session/user/settings/state backend retention periods**.
There is **no verified map of every file or derived cache deletion path**.
There is **no verified inventory of deployed endpoints or data-processing contracts**.
These gaps are not evidence that no data is stored or transferred.
They MUST be resolved from the actual repository and deployment before making corresponding public claims.

## 26. Maintainer procedure

When code changes processing behavior:

1. Inspect all relevant owners, interfaces, backend implementations, and active integration points.
2. Identify new or modified personal-data categories and entry/exit paths.
3. Update the table/file/cache/log/provider inventory based on exact source evidence.
4. Document retention, cleanup implementation and derived-copy deletion.
5. Update tests, contracts, README links and relevant subsystem docs in the same patch.
6. Record the review date, source revision and outstanding factual limits.
7. Do not approve release of new undocumented personal-data storage or inaccessible rights controls.

## 27. Applicable law and official references

- GDPR, Regulation (EU) 2016/679: <https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32016R0679>.
- ePrivacy Directive 2002/58/EC: <https://eur-lex.europa.eu/eli/dir/2002/58/oj>.
- European Accessibility Act, Directive (EU) 2019/882: <https://eur-lex.europa.eu/eli/dir/2019/882/oj>.
- Web Accessibility Directive (EU) 2016/2102: <https://eur-lex.europa.eu/eli/dir/2016/2102/oj>.
- WCAG 2.2: <https://www.w3.org/TR/WCAG22/>.
- EU privacy by design guidance: <https://www.edpb.europa.eu/documents/guideline/guidelines-42019-on-article-25-data-protection-by-design-and-by-default_en>.

## 28. Document history

| Date | Change | Basis |
| --- | --- | --- |
| 2026-10-14 | Initial BASE3 privacy inventory and evidence requirements | Architecture documentation and framework interface archive; full implementation verification outstanding |


### Authentication context in embedded BASE3 displays (2026-10-19)

`SelectedAccesscontrol::getUserId()` initializes the existing authentication-strategy lifecycle on first identity access if a host-embedded display did not run BASE3's access-control middleware. The same configured providers and their existing session/credential-processing behavior are used; authentication is not persisted by this change in a new BASE3 store. Requests that remain anonymous receive no authenticated user ID. The identity established by the host is then available for regular authorization and group evaluation. Operational retention and log behavior remain dependent on the configured authentication providers and hosting system.


### Declarative group and feature audience contracts (2026-10-19)

`UserCriterionEvaluator` is a read-only evaluator of a trusted host user,
configurable criteria, and optionally the current effective BASE3 group list.
Its `validate()` method checks the expression before a host chooses to persist it.
The `IGroup` contract describes discoverable code definitions; hosts may independently
store `Group` DTOs under their own settings backend. `IFeature::getDefaultAudience()`
describes a code-owned audience expression. None of these contracts writes an
authorization database or registers a recipient/transfer. Evaluations can process
identity attributes such as an email domain, user IDs and group names in memory;
stored configurations and their retention/deletion are exclusively the responsibility
of the host adapter and operator. For the reviewed ILIAS implementation see
`Base3Ilias/PRIVACY.md`. Third-party criterion plugins must document any additional
attributes or external recipients they process.


### Code-defined bootstrap membership and exact identity criteria (2026-10-19)

`Base3UserIdentityCriterion` compares explicit native account login names
(`User::userid`) and complete email addresses (`User::email`) that the active
host usermanager has already provided. Matching is performed in memory and
introduces no database, host lookups, network calls, recipient or new logging
mechanism in the framework. An empty allowlist grants nobody. Host adapters
remain responsible for verifying identity data, email ownership where needed,
and the native administrative authorization required for protected actions.

Project-owned `IGroup` implementations may embed exact identity allowlists in
source code. In that case project source, artifacts, backups and logs can become
repositories of personal data; the project owner must document its processing,
access restrictions, update/revocation workflow and retention in its root
`PRIVACY.md`. The framework does not maintain these allowlists or grant native
administrator permissions.

<!-- BASE3 XRM authorization migration 2026-10-19 -->
## Authorization migration: XRM server (source review 2026-10-19)

This update replaces legacy, mixed access administration with the shared
authorization decision, grant administration and native-role catalog interfaces.
Entry ACL records, user and group IDs, role assignments and permission identifiers
remain processed in the same pre-existing XRM storage and authenticated sessions;
no new authorization database tables are introduced by this patch. Changes are
requested through existing authenticated microservice transport. The server may
process current actor identity, native entry IDs and direct user/group grant
metadata in memory and on the native XRM database. The Manager client forwards
calls to that authority; it does not create a new authorization store.

Write responses are only marked successful after the server confirms completion.
Manager entry-grant forms require an authenticated session and a CSRF token.
Runtime logging, historic backups, live retention periods, cross-system operator
access and legally required privacy notices depend on the deployment and cannot
be verified from the selective supplied ZIPs. Deleting native grants is supported
through the XRM service, but does not automatically erase audit logs or backups.
Operators must check their own retention, notices, authentication settings,
recipient/transfer configuration and accessibility obligations.
