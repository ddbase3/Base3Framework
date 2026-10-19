# BASE3 Authorization Adapter Contract

## Scope

This is the implementer's guide for attaching BASE3 to an existing authorization
system without duplicating its rules. Read [authorization-model.md](authorization-model.md)
first. Native host operations, hierarchies and databases remain authoritative.

## Minimum implementation

An integration binds `IAuthorizationService` in the shared project container. It must:

1. Return the primary registered authority ID from `getDefaultAuthority()`.
2. Validate the exact `AuthorizationRequest::authority` and optional resource type/ID.
3. Resolve current subject exclusively through trusted host authentication. If an
   explicit `SubjectReference` is accepted, validate its authority/kind/id and ensure
   the caller is authorized to evaluate it; never trust browser fields as identity.
4. Map `Permission(scope,permission)` to one documented native operation, then call the
   native RBAC/activity/access API **directly**. Do not compute native parent-chain
   grants or reuse `IUsermanager::getPermissions()` as a substitute for object access.
5. Return `ALLOW`, `DENY` or `INDETERMINATE`, all non-allow outcomes non-executable.
6. Implement `decideMany()` using native preloading/batch APIs where useful, preserving
   exact keys/order, or safe individual native checks where no batch API exists.
7. Reject unimplemented scopes, unknown authorities, unsupported delegated principals
   and missing resource context rather than guessing a fallback.

An installation without an auth provider uses `NoAuthorizationService` and **denies**
access. A standalone project may select `Base3SystemAuthorizationService` for its
existing global BASE3 role/permission backend. It intentionally rejects unproven
resource permissions rather than claiming to implement entity ACLs.

### Project binding

```php
$container->set(
    IAuthorizationService::class,
    fn($c) => new HostAuthorizationService($c->get(IAccesscontrol::class)),
    IContainer::SHARED
);
```

Bind the native `IAuthorizationAdministrationService` separately only if the host
supports grant inspection/updates. An optional admin service does not change the
authoritative decision implementation. Feature and group UIs may additionally use the host's `ISettingsStore` for
BASE3-specific audience expressions, including direct users and discovered criteria.

## Native administration contract

Implement `describe()` truthfully: supported subjects, hierarchy capability, read/
write permissions **for the current operator** and meaningful limitations. Unauthorized
admin endpoints must deny, even when the UI was hidden. `getPermissions()` lists
operation definitions, `getSubjects()` enumerates eligible native subjects,
`getGrants()` gives real direct/effective grants including known inheritance,
and `replaceGrants()` atomically writes direct grants for one resource and subject.

Use native transactions/validation/cache invalidation. Do not invent an intermediate
BASE3 grant table or a new XRM/ILIAS settings-backed role hierarchy. Native grants
remain managed by the native backend. A read-only adapter must expose read-only
capabilities and reject unsupported writes.

## ILIAS 11 implementation

`Base3IliasAuthorizationService` delegates native object checks to
`ilRbacSystem::checkAccessOfUser($userId,$operation,$refId)` and bulk preloading to
`preloadRbacPaCache()`. This respects ILIAS's local/global role and inheritance
semantics. Repository rights use `ResourceReference('ilias','ref_id',$refId)`, not
`obj_id`; permission scope is `ilias` and operation names are ILIAS's native
operation identifiers. For native `create` checks, the domain action supplies a validated
`AuthorizationRequest::parameters['object_type']` (such as `crs`) to the native fourth
`checkAccessOfUser()` argument. BASE3 never reconstructs a create-permission mapping. Native system-admin status is resolved by ILIAS roles.

`Base3IliasAuthorizationAdministrationService` uses ILIAS `ilRbacReview` for role
subjects, active and direct operation IDs, `ilRbacAdmin::grantPermission()` for
native direct writes, and `ilDBInterface` transaction support for safe atomic changes.
Inherited permissions remain read-only in the common UI. Unsupported or protected
roles cannot be edited. Settings for code-owned BASE3 features are stored separately
using `Base3IliasFeatureConfiguration` through ILIAS-backed `ISettingsStore`.

The ILIAS group settings use `Base3IliasGroupConfiguration` with discoverable
`IGroup` implementations and host-created `Group` DTOs. `UserCriterionEvaluator` is
the **only** criterion-expression evaluator for groups and feature audiences; the
adapter relies on trusted native ILIAS identity/role resolution, never a second
RBAC walk. Native ILIAS RBAC is never silently modified by BASE3 groups. The immutable migration
`Migration001ConvertGroupAssignments` converts old `base3ilias/groups` `assignments`
into `criteria` before request execution; runtime group evaluation never rewrites old
settings. An additional immutable migration changes existing
`base3_authorization_features/catalog` `grants` lists into `rules` expressions
without altering permissions. Both admin UIs use the same repeatable-rule editor,
with schema-derived inputs for simple criteria and JSON for complex definitions.
The BASE3 → Groups, Features and Permissions setup tabs remain visible to native
admins without `development`; the remaining tabs require that BASE3 group.
Keep a settings backup before migration. Since `IClassMap` discovers
project criteria, a hard-coded `qualitus.de` employee criterion can be provided by a
project plugin without schema changes.

**Version support:** ILIAS Activities are optional and incomplete in the host roadmap.
Legacy/native ILIAS RBAC, local role operations, object inheritance and type-specific
checks remain the primary authorization path. A future activity integration may check
`isAllowedToPerform()` with validated parameters when that API genuinely governs an
action. Do not call a bare `perform()` as an unprotected shortcut. Verify native
signatures against each deployed ILIAS version (9, 10, 11, 12); this patch is scoped
to the uploaded ILIAS 11 instance.

## Multi-authority installs

A mixed ILIAS+XRM installation can register exact `ilias` and project-defined XRM
instance IDs. The `AuthorizationMultiplexer` maps authority IDs directly to decision
providers, and separately to optional native admin providers. No implicit permission
combination or user-ID equivalence exists. An XRM adapter should explicitly map a
trusted ILIAS subject to its authorized XRM principal, if required. The caller must
use a resource reference owned by its actual backend. Authority conflicts/missing
routes fail closed. Do not route by an untrusted browser-controlled authority name.

## Performance

- Prefer direct native authorization calls to duplicate framework interpretation.
- Preload native permission caches for `decideMany()` if the native host supports it.
- For repository trees, rely on the host's indexed, native hierarchy evaluation;
  do not recurse through every ancestor in BASE3 PHP code.
- For large XRM/ILIAS lists, push access-filter constraints into the authoritative
  query provider. Per-row post-load checks are NOT a safe or scalable primary filter.
- Changes must invalidate backend permission caches; avoid hidden long-lived cached
  `AuthorizationDecision` values, especially across users, resources and tenants.
- Rights administration may load a bounded resource's roles/grants; report or paginate
  unbounded subject catalogs rather than enumerating all host users.

## Adapter contract test checklist

| Case | Required outcome |
|---|---|
| Unknown authority/instance or foreign resource ID | Denied or indeterminate, never allowed |
| Current authenticated/anonymous subject | Correctly delegated to native host |
| Client attempts to impersonate a subject | Rejected unless independently authorized |
| Native allow/deny/error | ALLOW, DENY, INDETERMINATE respectively |
| Direct vs. inherited role right | Native effective decision; correct admin provenance where known |
| Protected rights and non-admin edits | Never mutated |
| Empty direct rights replacement | Explicit native revocation only |
| Invalid permissions/subjects | No partial writes |
| `decideMany()` keyed requests | Same decisions, exact keys/order, native batch/preload when available |
| Groups from user ID, email domain, ILIAS role | Discoverable and independently resolvable |
| Custom BASE3 groups and feature users/criteria | Canonical host dataset, single evaluator, no native RBAC writes |
| ILIAS administrator without `development` | Reduced tabs plus Groups/Features/Permissions setup |
| BASE3 `development` member | Full navigation regardless of a legacy Developer role |
| Feature visibility versus direct API endpoint | Both enforced at server boundary |
| Mixed ILIAS/XRM host | Exact authority routing; no implicit cross-authority grants |
| Cache and revocation | No stale authorization after native update |
| Large hierarchical resource lists | Native prefiltered access; no N+1 parent traversal |
| Unsupported admin operations | UI declares inability; explicit server-side rejection |

## XRM/Manager adaptation

Memora previously mixed generic roles/permissions with entry-ACL administration in
`IEntityAccessService`. This duplicate framework-level path is removed at the contract
level. During the coordinated XRM/Manager patch, Memora must expose its existing
native role/ACL owner as `IAuthorizationService` and its native grant administration as
`IAuthorizationAdministrationService`; Manager uses these shared contracts over its
existing authorized remote transport. Do **not** reintroduce `IEntityAccessService`
or add a legacy bridge; refactor the owning implementation and callers together.
Do not pull the breaking ResourceFoundation/framework change to live XRM/Manager
before their project-specific update is ready.

### ILIAS feature settings consistency

Feature-group overrides are written as one canonical `base3_authorization_features/catalog` settings dataset. The ILIAS settings backend owns the one durable update; changing several features must never result in a partially persisted authorization administration request. Code defaults apply only when a feature has no explicit entry, not when it has an empty list.
