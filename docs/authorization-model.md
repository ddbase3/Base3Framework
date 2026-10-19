# BASE3 Authorization Model

## Purpose

This specification defines the host-neutral authorization language of BASE3. BASE3 is a
contract layer, not an authorization database, not a new RBAC implementation and not a
second policy interpreter over an existing host. Native host and standalone backends
remain authoritative for users, role inheritance, object hierarchies, grants and cache
invalidations. Contracts are defined here so plugins and administration UIs can remain
portable across embedded installations.

**Normative rule:** An action is executable only when its authoritative
`IAuthorizationService::decide()` result is `AuthorizationDecision::ALLOW`.
Absence of a result, invalid input, unknown authorities, errors and indeterminate
outcomes never authorize execution. Visibility/UI checks are never a substitute for
server-side enforcement at the action boundary.

## Responsibility map

| Owner | Contract | Responsibility |
|---|---|---|
| BASE3 framework | `IUsermanager` | Current user, user lookup, groups, effective roles, identity metadata and supported role-management operations; no permission decision |
| BASE3 framework | `IUserCriterion` | Discoverable read-only user-membership predicate; never a permission grant |
| BASE3 framework | `IGroup` | Discoverable code-owned logical BASE3 group with optional configurable membership expression |
| BASE3 framework | `IFeature` | Discoverable plugin feature description, permission catalog and default audience expression |
| BASE3 framework | `IAuthorizationService` | **Only** authorization-decision contract, including native batch capability |
| BASE3 framework | `IAuthorizationAdministrationService` | Native right metadata, effective grants, supported UI operations, authorized atomic grant replacement |
| Implementation plugin / host | Concrete services | Authoritative storage, native permission and hierarchy evaluation, user-identity mapping and invalidation |
| Project plugin | Container bindings | Choose the active native services and explicit registered authority instances |

`IAccesscontrol` still establishes the current authenticated request identity; it does not
answer domain authorization questions. Roles and groups are not synonymous. A `Permission`
is the stable action identifier (`scope`, `permission`) and does **not** embed a resource
ID. An `IFeature` is descriptive metadata, not a second `Permission` or an executor.

## Request and identity model

`AuthorizationRequest` contains:

- `authority`: required exact authorization-authority **instance** ID, selected by project
  composition or copied from a trusted resource reference, never freely accepted from a
  browser as an authoritative routing decision.
- `permission`: stable `Base3\Usermanager\Permission` (action scope + operation).
- `resource`: optional `ResourceReference(authority,type,id)` for object-specific actions;
  its authority must match the request authority. The native backend owns all ancestors,
  path traversal and permission inheritance; BASE3 does not load parent chains.
- `subject`: optional `SubjectReference(authority,kind,id)`. Null is the current subject
  resolved from the trusted runtime, which may be anonymous. Do not confuse a user ID in
  one host with the same integer in another host.
- `parameters`: validated domain action parameters that affect authorization (for example,
  an activity's source and destination), not unrestricted user claims.
- `context`: trusted execution/tenant/MFA context established by the server. User-supplied
  fields must be validated before they may influence native authorization.
- `onBehalfOf`: explicit delegated principal, when a backend supports delegation. Native
  implementations must reject delegation they cannot authenticate and authorize.

A subject authority may differ from the resource authority: a mixed ILIAS/XRM project
may authenticate an ILIAS account and use an explicitly authorized identity mapping for
XRM. A host adapter must not equate IDs of different authorities without a trusted map.

`AuthorizationDecision` has three distinct outcomes: `ALLOW`, `DENY`, `INDETERMINATE`.
Only `ALLOW` is executable. Reason codes are short, sanitized and stable enough for
operator diagnostics, not a vehicle for leaking membership or credential data.

### Code

```php
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Authorization\Dto\ResourceReference;
use Base3\Usermanager\Permission;

$decision = $authorization->decide(new AuthorizationRequest(
    $authorization->getDefaultAuthority(),
    Permission::for('ilias', 'read'),
    new ResourceReference('ilias', 'ref_id', $refId)
));
if (!$decision->isAllowed()) {
    throw new RuntimeException('Access denied.');
}
```

The example assumes the project's current native authority is exactly `ilias`.
For other hosts use their registered instance ID consistently for **both** the request
and resource. Generic plugins should use `getDefaultAuthority()` for global BASE3
features, and the resource's explicit authority for resource-bound permissions.

### Effective access and inheritance

A host decides whether grants are inherited, blocked, locally overridden, implicitly
allowed or expressly denied. BASE3 imposes **no** global `deny-overrides` policy on
hosts with different semantics. A host admin adapter reports `AuthorizationGrant` with
`inherited=true/false` when it can determine provenance; `null` means unknown. An
optional `origin` gives a native source resource if known. Read and edit capabilities are
separate; inherited/protected grants must not be changed by generic replacement.

A framework-level `IUserCriterion` is **not** part of native role inheritance. It
can define membership in additional BASE3 groups or eligibility for a BASE3 feature,
but cannot silently grant ILIAS roles or native rights. The host enforces native
resource permissions independently.

## Discoverable membership criteria

`IGroup` and `IUserCriterion` extend `IBase`. The class map discovers implementations
under the framework and plugin `src/` trees. Their `getName()` is the lower-case
unnamespaced class name; it is globally unique. `IUserCriterion` also implements
`ISchemaProvider` for typed UI-configurable values. Project-specific predicates may
hard-code the complete rule in PHP without requiring any database record.

Membership expressions are arrays with **one** of the following node shapes:

```php
['criterion' => 'base3authenticatedcriterion', 'config' => []]
['criterion' => 'base3emaildomaincriterion', 'config' => ['domain' => 'qualitus.de']]
['any' => [$criterion1, $criterion2]]
['all' => [$criterion1, $criterion2]]
['not' => $criterion]
// Feature audiences may additionally use:
['group' => 'development']
```

An empty expression means no membership; compound lists must not be empty. Unknown,
invalid or excessively nested criteria fail closed including inside `not` expressions.
The evaluator is read-only and has no side effects on permission or role storage.
For example, an ILIAS project may define `QualitusEmployeeCriterion` whose
`matches()` checks the email domain, discoverable without any settings table. The
host is responsible for supplying trustworthy user attributes.

Groups such as `anonymous` and `authenticated` can be locked code definitions.
Editable code groups have host-managed criteria. A host may also persist additional
`Group` DTOs (not fake runtime `IGroup` implementations), keeping the discoverable
classmap group names and host-created names unique. Both kinds of groups use the same
`UserCriterionEvaluator`. Host-owned ILIAS role and user selectors are simply
criteria; they do not assign ILIAS RBAC roles. Group membership expressions must not
contain `group` leaves, avoiding circular memberships. The evaluator uses native
host roles resolved independently of BASE3 group membership.

Feature audiences may contain `group` references to current effective BASE3 groups,
ordinary criteria (such as `base3emaildomaincriterion`) or user-ID criteria. The
same `any`, `all`, `not` semantics apply to both groups and features. For configured
additive selectors, a top-level `any` contains the selections; complex conditions
can be declared directly by plugin code or edited as advanced JSON in a host UI.
`UserCriterionEvaluator::validate()` rejects unknown group references and invalid
criterion configurations before persistence. `matches()` is read-only and fail-closed
when the expression is invalid.

## Feature contracts

`IFeature` declares `getPermissions()` (one or more real `Permission` instances),
`getLabel()`, `getDescription()` and `getDefaultAudience()`.
The last method returns a declarative audience expression, allowing defaults based
on groups, users or plugin-provided criteria without a host settings record.
Permission identity is independent of the implementation name; duplicate feature-
permission ownership in an authority is an error, not a union of two rules.
A configured **empty** audience intentionally disables the feature. Missing settings
use the code-defined audience. Native host permissions can add further restrictions.
All feature overrides are owned by the host; BASE3 framework adds no database.

## Multiple authorization authorities

Standalone/embedded projects bind one `IAuthorizationService` and optionally one
`IAuthorizationAdministrationService` directly. For genuinely mixed installations,
`AuthorizationMultiplexer` routes **only** by exact authority ID to an explicitly
registered native service; decision and administration implementations are distinct
maps. It does not evaluate policies, combine grants, translate users, or infer rights.
An unknown/missing authority fails closed; the project chooses a `defaultAuthority`
for its ordinary host features. If one application action requires multiple independent
authorities, the *domain operation* must authorize every required part explicitly;
no implicit cross-authority `OR` is allowed.

## Administration

The common admin contract exposes `describe`, `getPermissions`, `getSubjects`,
`getGrants` and `replaceGrants`. Capability metadata makes a shared UI functional even
where a host cannot support some editing capabilities; no fake writes. Native resources
and role objects remain identified by `ResourceReference`/`SubjectReference`.
Administrative reads and writes require native server-side authorization.
`replaceGrants` replaces **only direct** editable grants for exactly one native
subject+resource, and empty lists mean intentional direct revocation. Native protected
and inherited grants are not changed. The backend must guarantee transactional
integrity and native permission-cache invalidation. If it cannot, the operation fails,
not partially applies.

## Performance and security invariants

1. `decideMany()` preserves all input keys and order and has the same result semantics
   as `decide()`. Native preloading and batch APIs are preferred when available.
2. For large resource lists, the source query/backend MUST apply native authorization-
   aware filters **before** returning rows. `decideMany()` is not a substitute for
   filtering thousands of loaded records in PHP. Query implementations in
   ResourceFoundation remain responsible for authorized result sets.
3. Do not introduce another BASE3 parent traversal, host-role cache, effective-permission
   cache, RBAC database or shadow permission path above the native authority.
4. Client-supplied IDs/context must be validated and bound to server-established
   identity. Delegated actors must be independently verified.
5. Feature or admin UI display filtering never replaces checks at the actual operation
   endpoint (including JSON APIs, tools, jobs and background execution).
6. Deny/indeterminate states must not leak sensitive native role or resource metadata.
7. The native owner invalidates permission caches on change; no stale session grants
   or cross-user/cross-tenant cached authorization results may survive revocation.
8. Hosts must document processing, access logging, settings retention, deletion and
   invalidation in their own root `PRIVACY.md` with source-backed facts.

## Versioning and migration

`IUsermanager::can()` and `Permission::$target` are deleted, not bridged. All consumers
must change to `IAuthorizationService` and `ResourceReference`. The former
ResourceFoundation `IEntityAccessService`/`EntityAccessProxy` duplicates are removed.
Memora and Base3Manager require an explicit coordinated update before pulling these
breaking common interfaces; do not deploy only the framework pull to live XRM/Manager.

The ILIAS host retains its real RBAC and repository hierarchy. Activities (where
supported) are an additional domain execution concept, not an RBAC replacement.


### Code-owned bootstrap groups

A discoverable `IGroup` may contain fixed membership criteria. These rules are
not overwritten by administrator-managed membership entries; supported additions
are evaluated in the same criterion evaluator with logical OR. Project plugins
can therefore declare exact deployment-specific bootstrap members without creating
a central database, hidden superuser branch or competing authorization evaluator.
The native authorization authority must still permit the requested administrative
action. Code-owned membership is not equivalent to native administrator privilege.
