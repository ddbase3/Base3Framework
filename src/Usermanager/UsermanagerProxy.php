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

namespace Base3\Usermanager;

use Base3\Usermanager\Api\IUsermanager;

/**
 * Proxy usermanager with request-lifetime caching.
 *
 * Fixes performance issues where each getUser() call triggers
 * a microservice HTTP request (~120ms).
 */
class UsermanagerProxy implements IUsermanager {

	private $connector;

	/** @var User|null */
	private ?User $cachedUser = null;

	/** @var Group[]|null */
	private ?array $cachedGroups = null;

	/** @var Role[]|null */
	private ?array $cachedRoles = null;

	/** @var Permission[]|null */
	private ?array $cachedPermissions = null;

	/** @var array|null */
	private ?array $cachedAllUsers = null;

	/** @var array|null */
	private ?array $cachedAllGroups = null;

	/** @var array|null */
	private ?array $cachedAllRoles = null;

	/** @var array|null */
	private ?array $cachedAllPermissions = null;

	public function __construct($connector) {
		$this->connector = $connector;
	}

	/**
	 * Returns the current user (cached).
	 */
	public function getUser() {
		if ($this->cachedUser !== null) {
			return $this->cachedUser;
		}

		$user = $this->connector->getUser();
		$this->cachedUser = is_array($user) ? User::fromArray($user) : $user;

		return $this->cachedUser;
	}

	/**
	 * Returns groups (cached).
	 */
	public function getGroups() {
		if ($this->cachedGroups !== null) {
			return $this->cachedGroups;
		}

		$groups = $this->connector->getGroups();

		if (is_array($groups) && isset($groups[0]) && is_array($groups[0])) {
			$this->cachedGroups = array_map(fn($g) => Group::fromArray($g), $groups);
		} else {
			$this->cachedGroups = $groups;
		}

		return $this->cachedGroups;
	}

	/**
	 * Returns effective roles (cached).
	 */
	public function getRoles() {
		if ($this->cachedRoles !== null) {
			return $this->cachedRoles;
		}

		$roles = $this->connector->getRoles();

		if (is_array($roles) && isset($roles[0]) && is_array($roles[0])) {
			$this->cachedRoles = array_map(fn($r) => Role::fromArray($r), $roles);
		} else {
			$this->cachedRoles = $roles;
		}

		return $this->cachedRoles;
	}

	/**
	 * Returns effective permissions (cached).
	 */
	public function getPermissions() {
		if ($this->cachedPermissions !== null) {
			return $this->cachedPermissions;
		}

		$permissions = $this->connector->getPermissions();

		if (is_array($permissions) && isset($permissions[0]) && is_array($permissions[0])) {
			$this->cachedPermissions = array_map(fn($p) => Permission::fromArray($p), $permissions);
		} else {
			$this->cachedPermissions = $permissions;
		}

		return $this->cachedPermissions;
	}

	public function hasRole(Role $role): bool {
		return (bool) $this->connector->hasRole($role);
	}

	public function can(Permission $permission): bool {
		return (bool) $this->connector->can($permission);
	}

	public function registUser($userid, $password, $data = null) {
		return $this->connector->registUser($userid, $password, $data);
	}

	public function changePassword($oldpassword, $newpassword) {
		return $this->connector->changePassword($oldpassword, $newpassword);
	}

	/**
	 * Returns all users (cached).
	 */
	public function getAllUsers() {
		if ($this->cachedAllUsers !== null) {
			return $this->cachedAllUsers;
		}

		$users = $this->connector->getAllUsers();

		if (is_array($users) && isset($users[0]) && is_array($users[0])) {
			$this->cachedAllUsers = array_map(fn($u) => User::fromArray($u), $users);
		} else {
			$this->cachedAllUsers = $users;
		}

		return $this->cachedAllUsers;
	}

	public function getAllGroups() {
		if ($this->cachedAllGroups !== null) {
			return $this->cachedAllGroups;
		}

		$groups = $this->connector->getAllGroups();

		if (is_array($groups) && isset($groups[0]) && is_array($groups[0])) {
			$this->cachedAllGroups = array_map(fn($g) => Group::fromArray($g), $groups);
		} else {
			$this->cachedAllGroups = $groups;
		}

		return $this->cachedAllGroups;
	}

	public function getAllRoles() {
		if ($this->cachedAllRoles !== null) {
			return $this->cachedAllRoles;
		}

		$roles = $this->connector->getAllRoles();

		if (is_array($roles) && isset($roles[0]) && is_array($roles[0])) {
			$this->cachedAllRoles = array_map(fn($r) => Role::fromArray($r), $roles);
		} else {
			$this->cachedAllRoles = $roles;
		}

		return $this->cachedAllRoles;
	}

	public function getAllPermissions() {
		if ($this->cachedAllPermissions !== null) {
			return $this->cachedAllPermissions;
		}

		$permissions = $this->connector->getAllPermissions();

		if (is_array($permissions) && isset($permissions[0]) && is_array($permissions[0])) {
			$this->cachedAllPermissions = array_map(fn($p) => Permission::fromArray($p), $permissions);
		} else {
			$this->cachedAllPermissions = $permissions;
		}

		return $this->cachedAllPermissions;
	}

	public function assignRoleToUser($userid, Role $role): bool {
		$this->clearAuthorizationCache();
		return (bool) $this->connector->assignRoleToUser($userid, $role);
	}

	public function revokeRoleFromUser($userid, Role $role): bool {
		$this->clearAuthorizationCache();
		return (bool) $this->connector->revokeRoleFromUser($userid, $role);
	}

	public function assignRoleToGroup($groupid, Role $role): bool {
		$this->clearAuthorizationCache();
		return (bool) $this->connector->assignRoleToGroup($groupid, $role);
	}

	public function revokeRoleFromGroup($groupid, Role $role): bool {
		$this->clearAuthorizationCache();
		return (bool) $this->connector->revokeRoleFromGroup($groupid, $role);
	}

	public function addPermissionToRole(Role $role, Permission $permission): bool {
		$this->clearAuthorizationCache();
		return (bool) $this->connector->addPermissionToRole($role, $permission);
	}

	public function removePermissionFromRole(Role $role, Permission $permission): bool {
		$this->clearAuthorizationCache();
		return (bool) $this->connector->removePermissionFromRole($role, $permission);
	}

	private function clearAuthorizationCache(): void {
		$this->cachedRoles = null;
		$this->cachedPermissions = null;
		$this->cachedAllRoles = null;
		$this->cachedAllPermissions = null;
	}
}
