# BASE3 Usermanager, Groups and Roles

The usermanager is the framework contract for identity metadata and effective group/
role relations. **Authorization decisions are a separate single service:**
`Base3\Authorization\Api\IAuthorizationService`.

## Terminology

- `User`: resolved native or standalone principal. The technical `id` is local to the
  owning identity authority; `userid` is the login identifier, not a global ID.
- `Group`: stable BASE3 collection of users; it can be code-owned and its membership
  computed via discoverable predicates. Native host groups are not automatically
  overwritten or converted to BASE3 groups.
- `Role`: named host- or backend-owned responsibility/profile, possibly inherited
  through native groups. The active backend owns its role relationships.
- `Permission`: atomic action `(scope,permission)` without any target field. For
  object-specific actions, the resource belongs to the `AuthorizationRequest`.

The `IUsermanager` contract includes current-user and lookup methods, group/role/
permission metadata, and optional legacy user/role mutation operations with no
universal authorization semantics. Implementations such as the ILIAS adapter can
refuse unsupported user-creation or role-mutation operations without inventing
extra storage. Administration of native **object grants** belongs to the
`IAuthorizationAdministrationService` chosen by project composition.

### Example

```php
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Usermanager\Permission;

$user = $usermanager->getUser();
$groups = $usermanager->getGroups();
$roles = $usermanager->getRoles();
$allowed = $authorization->decide(new AuthorizationRequest(
    $authorization->getDefaultAuthority(), Permission::for('system','admin')
))->isAllowed();
```

`IUsermanager::can()` has been deleted. Do not retain a parallel implementation,
proxy fallback or session-cached permission evaluator. The auth contract owns all
boolean permission decisions. Existing direct user/group/role metadata operations
are not themselves evidence that the current user can execute a protected action.

## User group criteria

`Base3\Usermanager\Api\IGroup` is discoverable and provides a `Group`, a default
criterion expression and `isEditable()`. `IUserCriterion` extends `IBase` and
`ISchemaProvider`; it checks one trusted user and optional typed configuration,
never grants a native permission. `UserCriterionEvaluator` interprets `criterion`,
`any`, `all`, `not`; invalid and unknown leaf types fail closed.

Examples supplied by the framework: `Base3AuthenticatedCriterion`,
`Base3AnonymousCriterion`, `Base3UserIdCriterion`, `Base3UserIdentityCriterion`,
`Base3EmailDomainCriterion`.
The host integration can contribute more (such as `Base3IliasRoleCriterion`). Project
plugins can implement fixed criteria without persistence, and `IClassMap` discovers
them. The host's current user, email and independently resolved native roles are
supplied to the criterion implementation. Never compute membership using effective
BASE3 roles that in turn depend on that same group.

User group criteria are optional application-level additions. They do not overwrite
native platform roles, impersonate users or circumvent native ACLs. For settings-backed
groups, persist the canonical expression in the host implementation of
`ISettingsStore` and use versioned migrations for old definitions.

## Backend and transport

BASE3 does not require a database to operate. Its default authentication and
user-manager bindings may intentionally be nonfunctional/deny-by-default.
A project/plugin selects an active `IUsermanager`, and a separate active
`IAuthorizationService`, through the container. A backend such as Memora may use
`IDatabase`; an embedded ILIAS/WordPress/Moodle instance delegates to the host's
own identity, role, capability and hierarchy APIs. A remote deployment maps exact
authority **instances** and never assumes user IDs are portable across systems.

For full request/decision/feature/admin semantics see
[authorization-model.md](authorization-model.md) and
[authorization-adapters.md](authorization-adapters.md).


## Discoverable and host-managed BASE3 groups

`IGroup` describes code-owned groups discoverable through `IClassMap` and is not a
factory for user-created groups. Host adapters may persist additional logical
`Group` DTOs and must prevent collisions with the discovered names. Both kinds of
groups contribute to `IUsermanager::getGroups()`. Membership is determined by the
shared `UserCriterionEvaluator` from native user attributes and plugin-defined
`IUserCriterion` implementations; evaluation never grants a native host role. Group
expressions cannot reference other BASE3 groups, avoiding cycles. Feature audiences
can reference effective BASE3 groups, users, host-native roles via adapter criteria,
or arbitrary discoverable criteria using the same evaluator.
See `authorization-model.md` for the normative expression grammar.


### Project-owned fixed group membership

Code-discovered `IGroup::getCriteria()` defines members who cannot be removed by a
host settings edit. If `IGroup::isEditable()` is true, the active adapter may OR
additional host-managed criteria into the same effective group expression. The
fixed expression and its managed additions are not parallel permission stores.
This matters for bootstrapping access to configuration interfaces without granting
a universal superuser override. A project owns such a bootstrap group and must
name exact, trusted login names or complete verified email addresses.
`Base3UserIdentityCriterion` uses the host-provided `User::userid` (login) and
`User::email` and is portable across hosts with different numeric user IDs.
Email addresses are suitable for privileged bootstrap only where their ownership
and modification are appropriately controlled by the host. Generic criteria
such as email domains
should not be used for privileged bootstrap identities.
