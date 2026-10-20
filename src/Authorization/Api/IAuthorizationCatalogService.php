<?php declare(strict_types=1);

namespace Base3\Authorization\Api;

/**
 * Native catalog and role-membership administration, not an authorization decision API.
 * This contract owns user/group-role assignments and global permission definitions.
 * Per-resource direct grants belong exclusively to IAuthorizationAdministrationService;
 * resource read/write authorization belongs to IAuthorizationService.
 * Implementations MUST require the native system administrator before returning any
 * protected catalog data or mutating roles and invalidate their effective RBAC caches.
 * All identifiers are native to the active authority; they MUST NOT be shared across hosts.
 * Unknown IDs and unsupported operations MUST fail closed. Mutation validation MUST
 * precede writes; bulk membership replacements MUST be atomic in the native backend.
 */
interface IAuthorizationCatalogService {
	/**
	 * Returns known roles.
	 *
	 * @param bool $includeArchived Whether archived roles should be returned
	 * @return array<int,array<string,mixed>> Role rows
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Read-only metadata lookup; archived definitions are omitted unless explicitly requested. Empty means none. Never confers any permission.
	 */
	public function getRoles(bool $includeArchived = false): array;

	/**
	 * Returns one role by identifier.
	 *
	 * @param int|string $roleId Role identifier
	 * @return array<string,mixed>|null Role row or null
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Read-only lookup of exactly one native role. Null means it does not exist. Includes related permission definitions without deriving an access decision.
	 */
	public function getRole(int|string $roleId): ?array;

	/**
	 * Creates a role.
	 *
	 * Common role keys are name, label, info, archive, permission_ids, and permissions.
	 *
	 * @param array<string,mixed> $role Role data
	 * @return int|string Created role identifier
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Creates one native role and optionally its role-permission relations. Name must be non-empty and unique. Implementations may return an existing ID for an identical native role; an insert failure must throw, never return 0. Partial writes must not be left behind.
	 */
	public function createRole(array $role): int|string;

	/**
	 * Updates a role.
	 *
	 * @param int|string $roleId Role identifier
	 * @param array<string,mixed> $patch Partial role data
	 * @return void The native operation completed; failures throw.
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Only declared mutable metadata fields may change. Identity and protected roles must not be silently overwritten. Empty patches must not mutate state.
	 */
	public function updateRole(int|string $roleId, array $patch): void;

	/**
	 * Archives a role.
	 *
	 * @param int|string $roleId Role identifier
	 * @return void The native operation completed; failures throw.
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Soft-archives a native role; does not physically erase historical relationship records. Effective rights must cease immediately according to native authorization rules.
	 */
	public function archiveRole(int|string $roleId): void;

	/**
	 * Returns known permissions.
	 *
	 * @param bool $includeArchived Whether archived permissions should be returned
	 * @return array<int,array<string,mixed>> Permission rows
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Read-only list of globally defined native permission entries, not the caller’s own effective rights. Archived definitions are omitted by default.
	 */
	public function getPermissions(bool $includeArchived = false): array;

	/**
	 * Returns one permission by identifier.
	 *
	 * @param int|string $permissionId Permission identifier
	 * @return array<string,mixed>|null Permission row or null
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Read-only lookup of one global native permission definition. Null denotes a missing ID and does not grant a right.
	 */
	public function getPermission(int|string $permissionId): ?array;

	/**
	 * Creates a permission.
	 *
	 * Common permission keys are scope, permission, label, info, and archive.
	 *
	 * @param array<string,mixed> $permission Permission data
	 * @return int|string Created permission identifier
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Creates a native permission definition with nonempty scope and operation. Does not assign it to a role. The native unique scope/operation constraint must be respected.
	 */
	public function createPermission(array $permission): int|string;

	/**
	 * Updates a permission.
	 *
	 * @param int|string $permissionId Permission identifier
	 * @param array<string,mixed> $patch Partial permission data
	 * @return void The native operation completed; failures throw.
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Changes mutable catalog metadata for the named native permission. Changes affecting scope/operation require native validation and effective-rights cache invalidation.
	 */
	public function updatePermission(int|string $permissionId, array $patch): void;

	/**
	 * Archives a permission.
	 *
	 * @param int|string $permissionId Permission identifier
	 * @return void The native operation completed; failures throw.
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Archives a permission definition without deleting audit/history records. Archived permissions must not authorize new actions.
	 */
	public function archivePermission(int|string $permissionId): void;

	/**
	 * Returns permissions assigned to a role.
	 *
	 * @param int|string $roleId Role identifier
	 * @return array<int,array<string,mixed>> Permission rows
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Lists direct native permission definitions attached to the specified role. Does not imply the requesting actor holds them.
	 */
	public function getRolePermissions(int|string $roleId): array;

	/**
	 * Replaces all permissions assigned to a role.
	 *
	 * @param int|string $roleId Role identifier
	 * @param array<int,int|string> $permissionIds Permission identifiers
	 * @return void The native operation completed; failures throw.
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Atomically replaces the complete direct permission membership of one existing native role; [] revokes all direct permissions. Validate all IDs before any mutation and invalidate effective RBAC caches.
	 */
	public function replaceRolePermissions(int|string $roleId, array $permissionIds): void;

	/**
	 * Returns roles directly assigned to a user.
	 *
	 * @param int|string $userId User identifier
	 * @return array<int,array<string,mixed>> Role rows
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Returns direct native role assignments for the identified user, excluding roles inherited through groups; no permission decision is made.
	 */
	public function getUserRoles(int|string $userId): array;

	/**
	 * Returns roles directly assigned to a group.
	 *
	 * @param int|string $groupId Group identifier
	 * @return array<int,array<string,mixed>> Role rows
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Returns direct native group-to-role assignments. The group is native to the owning authority, never a cross-host BASE3 criterion group.
	 */
	public function getGroupRoles(int|string $groupId): array;

	/**
	 * Returns effective roles for a user, combining direct user roles and roles
	 * inherited through the user's groups.
	 *
	 * @param int|string $userId User identifier
	 * @return array<int,array<string,mixed>> Role rows
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Returns direct plus native-group-inherited roles for exactly the named user. Do not recursively infer BASE3 criterion groups or perform cross-authority ID translation.
	 */
	public function getEffectiveUserRoles(int|string $userId): array;

	/**
	 * Replaces all roles directly assigned to a user.
	 *
	 * @param int|string $userId User identifier
	 * @param array<int,int|string> $roleIds Role identifiers
	 * @return void The native operation completed; failures throw.
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Atomically replaces all direct roles of the native user. [] removes direct roles but never group-inherited assignments. Reject unknown IDs before mutating, invalidate the native cache.
	 */
	public function replaceUserRoles(int|string $userId, array $roleIds): void;

	/**
	 * Replaces all roles directly assigned to a group.
	 *
	 * @param int|string $groupId Group identifier
	 * @param array<int,int|string> $roleIds Role identifiers
	 * @return void The native operation completed; failures throw.
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Atomically replaces the named native group’s role relations. [] revokes all group roles, never deletes the group or changes its user memberships.
	 */
	public function replaceGroupRoles(int|string $groupId, array $roleIds): void;

	/**
	 * Returns group identifiers for a user.
	 *
	 * @param int|string $userId User identifier
	 * @return array<int,int> Group identifiers
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Lists directly assigned native group IDs for a user, not the union of discoverable code-group or email criteria. A missing user yields no authority.
	 */
	public function getUserGroups(int|string $userId): array;

	/**
	 * Replaces all group memberships for a user.
	 *
	 * @param int|string $userId User identifier
	 * @param array<int,int|string> $groupIds Group identifiers
	 * @return void The native operation completed; failures throw.
	 * @throws \RuntimeException If the caller lacks native administration rights or storage fails
	 * @throws \InvalidArgumentException For invalid native IDs or payloads
	 * Atomically replaces direct native user-group relations for the named user; [] removes all direct relations. Validate all subjects and invalidate derived role access caches.
	 */
	public function replaceUserGroups(int|string $userId, array $groupIds): void;
}
