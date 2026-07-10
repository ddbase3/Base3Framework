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

/**
 * Interface IUsermanager
 *
 * Defines user, group, role and permission management operations.
 */
interface IUsermanager {

	/**
	 * Returns the current user data or identifier.
	 *
	 * @return mixed User object, array, or ID depending on implementation
	 */
	public function getUser();

	/**
	 * Returns the groups associated with the current user.
	 *
	 * @return array List of group identifiers or group data
	 */
	public function getGroups();

	/**
	 * Returns the effective roles associated with the current user.
	 *
	 * @return array List of Role objects or role data
	 */
	public function getRoles();

	/**
	 * Returns the effective global permissions associated with the current user.
	 *
	 * Target-specific permissions are normally checked through can().
	 *
	 * @return array List of Permission objects or permission data
	 */
	public function getPermissions();

	/**
	 * Checks whether the current user has the given role.
	 */
	public function hasRole(Role $role): bool;

	/**
	 * Checks whether the current user may perform the given permission.
	 *
	 * Permission objects consist of scope, permission and target.
	 * Global permissions use target null. Target-specific adapters may
	 * require a concrete target, for example an external object ID.
	 */
	public function can(Permission $permission): bool;

	/**
	 * Registers a new user with the given ID, password, and optional additional data.
	 *
	 * @param string|int $userid Unique user ID
	 * @param string $password Plaintext password
	 * @param mixed|null $data Optional additional user data (e.g. array or object)
	 * @return bool True on success, false on failure
	 */
	public function registUser($userid, $password, $data = null);

	/**
	 * Changes the password for the current user.
	 *
	 * @param string $oldpassword Current password
	 * @param string $newpassword New password to set
	 * @return bool True if the password was changed successfully
	 */
	public function changePassword($oldpassword, $newpassword);

	/**
	 * Returns a list of all registered users.
	 *
	 * @return array List of users (format depends on implementation)
	 */
	public function getAllUsers();

	/**
	 * Returns a list of all groups.
	 *
	 * @return array List of groups (format depends on implementation)
	 */
	public function getAllGroups();

	/**
	 * Returns a list of all roles.
	 *
	 * @return array List of roles (format depends on implementation)
	 */
	public function getAllRoles();

	/**
	 * Returns a list of all permissions.
	 *
	 * @return array List of permissions (format depends on implementation)
	 */
	public function getAllPermissions();

	/**
	 * Assigns a role to a user.
	 */
	public function assignRoleToUser($userid, Role $role): bool;

	/**
	 * Revokes a role from a user.
	 */
	public function revokeRoleFromUser($userid, Role $role): bool;

	/**
	 * Assigns a role to a group.
	 */
	public function assignRoleToGroup($groupid, Role $role): bool;

	/**
	 * Revokes a role from a group.
	 */
	public function revokeRoleFromGroup($groupid, Role $role): bool;

	/**
	 * Adds a permission to a role.
	 */
	public function addPermissionToRole(Role $role, Permission $permission): bool;

	/**
	 * Removes a permission from a role.
	 */
	public function removePermissionFromRole(Role $role, Permission $permission): bool;

}
