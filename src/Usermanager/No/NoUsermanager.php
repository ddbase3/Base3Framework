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

namespace Base3\Usermanager\No;

use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\Permission;
use Base3\Usermanager\Role;

class NoUsermanager implements IUsermanager {

	// Implementation of IUsermanager

	public function getUser() {
		return null;
	}

	public function getGroups() {
		return array();
	}

	public function getRoles() {
		return array();
	}

	public function getPermissions() {
		return array();
	}

	public function hasRole(Role $role): bool {
		return false;
	}

	public function can(Permission $permission): bool {
		return false;
	}

	public function registUser($userid, $password, $data = null) {
		return false;
	}

	public function changePassword($oldpassword, $newpassword) {
		return false;
	}

	public function getAllUsers() {
		return array();
	}

	public function getAllGroups() {
		return array();
	}

	public function getAllRoles() {
		return array();
	}

	public function getAllPermissions() {
		return array();
	}

	public function assignRoleToUser($userid, Role $role): bool {
		return false;
	}

	public function revokeRoleFromUser($userid, Role $role): bool {
		return false;
	}

	public function assignRoleToGroup($groupid, Role $role): bool {
		return false;
	}

	public function revokeRoleFromGroup($groupid, Role $role): bool {
		return false;
	}

	public function addPermissionToRole(Role $role, Permission $permission): bool {
		return false;
	}

	public function removePermissionFromRole(Role $role, Permission $permission): bool {
		return false;
	}
}
