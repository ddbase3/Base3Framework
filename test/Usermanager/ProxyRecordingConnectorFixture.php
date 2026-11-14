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

namespace Base3\Test\Usermanager;

use Base3\CoreFoundation\Usermanager\Permission;
use Base3\CoreFoundation\Usermanager\Role;

final class ProxyRecordingConnectorFixture {

	public array $responses = [];
	public array $usersById = [];
	public array $calls = [];

	private function record(string $operation, array $arguments = []): mixed {
		$this->calls[] = [$operation, $arguments];
		return $this->responses[$operation] ?? null;
	}

	public function countCalls(string $operation): int {
		return count(array_filter($this->calls, static fn(array $call): bool => $call[0] === $operation));
	}

	public function getUser() { return $this->record('getUser'); }
	public function getUserById(int|string $id) {
		$this->record('getUserById', [$id]);
		return $this->usersById[(string)$id] ?? null;
	}
	public function getGroups() { return $this->record('getGroups'); }
	public function getRoles() { return $this->record('getRoles'); }
	public function getPermissions() { return $this->record('getPermissions'); }
	public function getAllUsers() { return $this->record('getAllUsers'); }
	public function getAllGroups() { return $this->record('getAllGroups'); }
	public function getAllRoles() { return $this->record('getAllRoles'); }
	public function getAllPermissions() { return $this->record('getAllPermissions'); }
	public function hasRole(Role $role) { return $this->record('hasRole', [$role]); }
	public function registUser($login, $password, $data = null) { return $this->record('registUser', [$login, $password, $data]); }
	public function changePassword($old, $new) { return $this->record('changePassword', [$old, $new]); }
	public function assignRoleToUser($user, Role $role) { return $this->record('assignRoleToUser', [$user, $role]); }
	public function revokeRoleFromUser($user, Role $role) { return $this->record('revokeRoleFromUser', [$user, $role]); }
	public function assignRoleToGroup($group, Role $role) { return $this->record('assignRoleToGroup', [$group, $role]); }
	public function revokeRoleFromGroup($group, Role $role) { return $this->record('revokeRoleFromGroup', [$group, $role]); }
	public function addPermissionToRole(Role $role, Permission $permission) { return $this->record('addPermissionToRole', [$role, $permission]); }
	public function removePermissionFromRole(Role $role, Permission $permission) { return $this->record('removePermissionFromRole', [$role, $permission]); }
}
