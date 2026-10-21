<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Usermanager\Api;

use Base3\Usermanager\Permission;
use Base3\Usermanager\Role;
use Base3\Usermanager\User;

/**
 * Identity, membership and role metadata exposed by the active native host backend.
 *
 * This contract is NOT an authorization decision engine. Neither a role returned from
 * getRoles() nor a permission listed by getPermissions() authorizes a resource action:
 * consumers MUST use IAuthorizationService for allow/deny decisions. Host/database
 * implementations remain authoritative for their users and role membership.
 */
interface IUsermanager {

	/**
	 * Reads the current native user associated with the authenticated request.
	 * Anonymous or unresolved identities return null. No authorization decision is made.
	 *
	 * @return User|null The current host user, or null for anonymous/unknown actors
	 */
	public function getUser();

	/**
	 * Looks up a native user without assuming IDs are shared between different authorities.
	 * The result describes identity and may include native role metadata, not an access grant.
	 *
	 * @param int|string $id Host-specific User::$id, not the display login or a cross-host ID
	 * @return User|null The matched native user, or null when absent/unsupported
	 */
	public function getUserById(int|string $id): ?User;

	/**
	 * Returns the current user's host-provided or BASE3-discoverable group memberships.
	 * Native RBAC inheritance and group criteria remain owned by their respective host
	 * implementations; a group is NOT, by itself, a native resource permission grant.
	 *
	 * @return array List of Group objects representing effective current membership
	 */
	public function getGroups();

	/**
	 * Returns roles independently resolved by the current host identity backend.
	 * A host adapter MUST avoid resolving a role from the same BASE3 group expression
	 * that depends on the role (no cyclic derived membership).
	 *
	 * @return array List of effective native Role objects for the current user
	 */
	public function getRoles();

	/**
	 * Lists global/current-user RBAC permission metadata known to this backend.
	 * Object-specific permissions MUST instead be checked through IAuthorizationService.
	 * The list is descriptive and cannot be treated as an authorization decision.
	 *
	 * @return array List of Permission objects or host-provided permission metadata
	 */
	public function getPermissions();

	/**
	 * Checks identity membership in a host-resolved role; does NOT grant an operation.
	 * Implementations may resolve known host IDs or stable names. Unknown roles deny.
	 *
	 * @param Role $role Native role identifier/name to check for the current user
	 * @return bool True only when the native current user has that role
	 */
	public function hasRole(Role $role): bool;

	/**
	 * Registers a native user if the active identity backend supports this operation.
	 * The host remains responsible for validation, hashing, account security and storage.
	 * This is not a substitute for an authenticated/authorized registration flow.
	 *
	 * @param int|string $userid Host-specific unique login or account identifier
	 * @param string $password Initial plaintext credential, handled securely by the native backend
	 * @param mixed $data Optional native user registration metadata
	 * @return bool True when stored, false when unsupported or rejected
	 */
	public function registUser($userid, $password, $data = null);

	/**
	 * Changes the current user's credential only through supported native host behavior.
	 * The host remains responsible for verifying the old credential and invalidating sessions.
	 *
	 * @param string $oldpassword Supplied existing credential
	 * @param string $newpassword Requested replacement credential
	 * @return bool True on successful native change, false when unsupported/rejected
	 */
	public function changePassword($oldpassword, $newpassword);

	/**
	 * Enumerates users visible through this backend. Some hosts intentionally offer only
	 * the current user; callers MUST NOT assume unrestricted native user-directory access.
	 *
	 * @return array List of accessible native User objects; potentially limited by host
	 */
	public function getAllUsers();

	/**
	 * Searches the native user directory for a literal, case-insensitive substring
	 * in the login, first name, last name, display/full name or email fields
	 * supported by the backend. An all-digit query additionally matches a native
	 * user ID exactly. The query is plain text, never SQL or a JSON expression.
	 * Surrounding whitespace is ignored. An empty/whitespace-only query returns [].
	 * SQL wildcards in input are literal characters, not query operators.
	 *
	 * Results MUST be fetched through the native backend with server-side search,
	 * limit and offset, not by loading getAllUsers() and filtering it in PHP.
	 * Return results in a deterministic order (display name, then native user ID)
	 * so pages can be requested consistently. Results describe identity only;
	 * expensive role/membership expansion is not required. A caller MUST NOT infer
	 * authorization from a returned user. Host IDs remain authority-specific.
	 *
	 * This method does not authorize access to the directory. The caller MUST
	 * enforce authorization before exposing results, particularly over HTTP.
	 * Implementations must not introduce new persistence or log query contents.
	 * Native backends may expose only users visible to the calling context.
	 *
	 * @param string $query Literal free-text search, trimmed before matching
	 * @param int $limit Number of users to return, in the range 1..100 (default 20)
	 * @param int $offset Non-negative count of users to skip (default 0)
	 * @return User[] Matching native users (possibly empty), with identity fields
	 * @throws \InvalidArgumentException If limit is outside 1..100 or offset is negative
	 * @throws \Throwable On native backend/transport failure (do not return arbitrary users)
	 */
	public function searchUsers(string $query, int $limit = 20, int $offset = 0): array;

	/**
	 * Enumerates logical groups exposed by this host or discoverable BASE3 group plugins.
	 * A missing group does not implicitly authorize its members or permissions.
	 *
	 * @return array List of host-visible Group objects
	 */
	public function getAllGroups();

	/**
	 * Enumerates host-exposed role information; hosts may limit the list to visible roles.
	 * For resource-specific role administration, use IAuthorizationAdministrationService.
	 *
	 * @return array List of host-visible Role objects
	 */
	public function getAllRoles();

	/**
	 * Enumerates permission definitions (catalog metadata, not a list of allowed actions).
	 * Resource-specific operations and granted rights remain under their native authority.
	 *
	 * @return array List of Permission definitions exposed by the host
	 */
	public function getAllPermissions();

	/**
	 * Adds a native role assignment to a native user where supported. The caller must
	 * already be authorized for user-role administration by the host operation boundary.
	 * Changes must invalidate the backend's effective role/permission caches.
	 *
	 * @param int|string $userid Host-specific target user identifier
	 * @param Role $role Existing host-native role to assign
	 * @return bool True only when successfully applied; false if unsupported/rejected
	 */
	public function assignRoleToUser($userid, Role $role): bool;

	/**
	 * Removes a previously granted native role membership from a user, when supported.
	 * The operation requires native administrative authorization and cache invalidation.
	 *
	 * @param int|string $userid Host-specific target user identifier
	 * @param Role $role Existing host role to revoke
	 * @return bool True only when successfully applied; false if unsupported/rejected
	 */
	public function revokeRoleFromUser($userid, Role $role): bool;

	/**
	 * Grants a native role to members of a native group, only where that host supports
	 * such role inheritance. Does not turn BASE3 criteria into implicit native RBAC.
	 * The caller must be authorized; the backend invalidates its role caches.
	 *
	 * @param int|string $groupid Native host group identifier
	 * @param Role $role Host-native role to associate
	 * @return bool True when applied; false for unsupported/rejected operations
	 */
	public function assignRoleToGroup($groupid, Role $role): bool;

	/**
	 * Revokes a native role/group relationship, where supported, without modifying
	 * independent BASE3 group criteria. Native administrative authorization is required.
	 *
	 * @param int|string $groupid Native group whose role relationship changes
	 * @param Role $role Existing host-native role
	 * @return bool True when applied; false for unsupported/rejected operations
	 */
	public function revokeRoleFromGroup($groupid, Role $role): bool;

	/**
	 * Adds a GLOBAL permission definition to a native role where supported.
	 * Resource-specific direct grants belong to IAuthorizationAdministrationService,
	 * never to this method. Caller authorization and native cache invalidation are required.
	 *
	 * @param Role $role Native role being changed
	 * @param Permission $permission Global permission identifier (scope + operation)
	 * @return bool True when applied; false for unsupported/rejected operations
	 */
	public function addPermissionToRole(Role $role, Permission $permission): bool;

	/**
	 * Removes a GLOBAL permission from a native role where supported, leaving native
	 * resource-specific ACL and other roles untouched. Caller authorization is required.
	 *
	 * @param Role $role Native role being changed
	 * @param Permission $permission Global permission to revoke
	 * @return bool True when applied; false for unsupported/rejected operations
	 */
	public function removePermissionFromRole(Role $role, Permission $permission): bool;
}
