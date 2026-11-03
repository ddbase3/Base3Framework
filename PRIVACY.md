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
