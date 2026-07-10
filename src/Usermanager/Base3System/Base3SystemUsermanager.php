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

namespace Base3\Usermanager\Base3System;

use Base3\Core\ServiceLocator;
use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\Group;
use Base3\Usermanager\Permission;
use Base3\Usermanager\Role;
use Base3\Usermanager\User;
use Base3\Api\ICheck;

class Base3SystemUsermanager implements IUsermanager, ICheck {

	private $servicelocator;
	private $database;
	private $accesscontrol;
	private $session;

	private $user;
	private $userrow;
	private $groups;
	private $roles;
	private $permissions;
	private $allgroups;
	private $allroles;
	private $allpermissions;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
		$this->accesscontrol = $this->servicelocator->get('accesscontrol');
		$this->session = $this->servicelocator->get('session');

		if ($this->session && $this->session->started() && isset($_SESSION["authentication"])) {
			if (isset($_SESSION["authentication"]["user"]))
				$this->user = $_SESSION["authentication"]["user"];
			if (isset($_SESSION["authentication"]["groups"]))
				$this->groups = $_SESSION["authentication"]["groups"];
			if (isset($_SESSION["authentication"]["roles"]))
				$this->roles = $_SESSION["authentication"]["roles"];
			if (isset($_SESSION["authentication"]["permissions"]))
				$this->permissions = $_SESSION["authentication"]["permissions"];
		}
	}

	// Implementation of IUsermanager

	public function getUser() {
		if ($this->user) return $this->user;

		$userid = $this->accesscontrol->getUserId();
		if (!$userid) return null;

		if ($userid == "internal") {

			$this->user = new User;
			$this->user->id = "internal";
			$this->user->userid = "internal";
			$this->user->name = "internal";
			$this->user->role = "admin";
			$this->user->roles = $this->getRoles();

		} else {

			$row = $this->getCurrentUserRow();
			if ($row == null) return null;

			$legacyRoles = array(0 => "visit", 1 => "member", 2 => "admin");

			$this->user = new User;
			$this->user->id = $row["id"];
			$this->user->userid = $row["userid"];
			$this->user->name = $row["fullname"];
			$this->user->email = $row["email"];
			$this->user->lang = $row["lang"];
			$this->user->role = $legacyRoles[(int)$row["mode"]] ?? "visit";
			$this->user->roles = $this->getRoles();
			$this->user->role = $this->resolveCompatibilityRole($this->user->roles, $this->user->role);

		}

		if ($this->session && $this->session->started()) {
			$_SESSION["authentication"]["user"] = $this->user;
		}

		return $this->user;
	}

	public function getGroups() {
		$userid = $this->accesscontrol->getUserId();

		if (!$userid) return array();
		if ($this->groups) return $this->groups;
		if ($userid == "internal") return array();

		$userId = $this->getCurrentUserDatabaseId();
		if (!$userId) return array();

		$this->database->connect();

		$sql = "SELECT g.`id`, g.`name`, g.`info`, g.`archive`
			FROM `base3system_sysusergroup` ug
			INNER JOIN `base3system_sysgroup` g ON ug.`group_id` = g.`id`
			WHERE ug.`user_id` = " . (int)$userId . "
			  AND g.`archive` = 0
			ORDER BY g.`name`";

		$rows = $this->database->multiQuery($sql);
		$this->groups = array();
		foreach ($rows as $row) {
			$this->groups[] = Group::fromArray($row);
		}

		if ($this->session && $this->session->started()) {
			$_SESSION["authentication"]["groups"] = $this->groups;
		}

		return $this->groups;
	}

	public function getRoles() {
		$userid = $this->accesscontrol->getUserId();

		if (!$userid) return array();
		if ($this->roles) return $this->roles;

		if ($userid == "internal") {
			$role = Role::named("admin");
			$this->roles = array($role);
			return $this->roles;
		}

		$userId = $this->getCurrentUserDatabaseId();
		if (!$userId) return array();

		$this->database->connect();

		$sql = "SELECT DISTINCT r.`id`, r.`name`, r.`label`, r.`info`, r.`archive`
			FROM `base3system_sysrole` r
			INNER JOIN `base3system_sysuserrole` ur ON ur.`role_id` = r.`id`
			WHERE ur.`user_id` = " . (int)$userId . "
			  AND r.`archive` = 0
			UNION
			SELECT DISTINCT r.`id`, r.`name`, r.`label`, r.`info`, r.`archive`
			FROM `base3system_sysrole` r
			INNER JOIN `base3system_sysgrouprole` gr ON gr.`role_id` = r.`id`
			INNER JOIN `base3system_sysusergroup` ug ON ug.`group_id` = gr.`group_id`
			WHERE ug.`user_id` = " . (int)$userId . "
			  AND r.`archive` = 0
			ORDER BY `name`";

		$rows = $this->database->multiQuery($sql);
		$this->roles = array();
		foreach ($rows as $row) {
			$this->roles[] = Role::fromArray($row);
		}

		if ($this->session && $this->session->started()) {
			$_SESSION["authentication"]["roles"] = $this->roles;
		}

		return $this->roles;
	}

	public function getPermissions() {
		$userid = $this->accesscontrol->getUserId();

		if (!$userid) return array();
		if ($this->permissions) return $this->permissions;

		if ($userid == "internal") {
			$this->permissions = array(Permission::for("system", "admin", null));
			return $this->permissions;
		}

		$roleIds = array();
		foreach ($this->getRoles() as $role) {
			if (isset($role->id) && is_numeric($role->id)) {
				$roleIds[] = (int)$role->id;
			}
		}

		if (empty($roleIds)) {
			$this->permissions = array();
			return $this->permissions;
		}

		$this->database->connect();

		$sql = "SELECT DISTINCT p.`id`, p.`scope`, p.`permission`, p.`label`, p.`info`, p.`archive`
			FROM `base3system_syspermission` p
			INNER JOIN `base3system_sysrolepermission` rp ON rp.`permission_id` = p.`id`
			WHERE rp.`role_id` IN (" . implode(",", $roleIds) . ")
			  AND p.`archive` = 0
			ORDER BY p.`scope`, p.`permission`";

		$rows = $this->database->multiQuery($sql);
		$this->permissions = array();
		foreach ($rows as $row) {
			$this->permissions[] = Permission::fromArray($row);
		}

		if ($this->session && $this->session->started()) {
			$_SESSION["authentication"]["permissions"] = $this->permissions;
		}

		return $this->permissions;
	}

	public function hasRole(Role $role): bool {
		$userid = $this->accesscontrol->getUserId();
		if ($userid == "internal" && $role->name == "admin") return true;

		foreach ($this->getRoles() as $currentRole) {
			if (isset($role->id, $currentRole->id) && $role->id && $currentRole->id == $role->id) return true;
			if (isset($role->name, $currentRole->name) && $role->name && $currentRole->name == $role->name) return true;
		}

		return false;
	}

	public function can(Permission $permission): bool {
		$userid = $this->accesscontrol->getUserId();
		if ($userid == "internal") return true;

		foreach ($this->getPermissions() as $currentPermission) {
			if ($this->isSystemAdminPermission($currentPermission)) {
				return true;
			}

			if ($this->permissionCovers($currentPermission, $permission)) {
				return true;
			}
		}

		return false;
	}

	public function registUser($userid, $password, $data = null) {
		// userid/password for authentication
		// data for two-way-auth (i.e. mail, mobile-no, ...) and fullname, language etc.

		$this->database->connect();

		// TODO
		return false;
	}

	public function changePassword($oldpassword, $newpassword) {
		$userid = $this->accesscontrol->getUserId();
		if (!$userid) return false;

		$this->database->connect();

		// TODO
		return false;
	}

	public function getAllUsers() {

		$this->database->connect();

		$sql = "SELECT u.`id`, u.`name` AS `userid`, u.`fullname`, u.`email`, u.`mode`, l.`short` AS `lang`
			FROM `base3system_sysuser` u
			INNER JOIN `base3system_syslang` l ON u.`lang_id` = l.`id`
			WHERE u.`id` != 1
			ORDER BY u.`fullname`";
		$rows = $this->database->multiQuery($sql);

		$legacyRoles = array(0 => "visit", 1 => "member", 2 => "admin");

		$users = array();
		foreach ($rows as $row) {
			$user = new User;
			$user->id = $row["id"];
			$user->userid = $row["userid"];
			$user->name = $row["fullname"];
			$user->email = $row["email"];
			$user->lang = $row["lang"];
			$user->role = $legacyRoles[(int)$row["mode"]] ?? "visit";
			$user->roles = $this->getRolesForUserId((int)$row["id"]);
			$user->role = $this->resolveCompatibilityRole($user->roles, $user->role);
			$users[] = $user;
		}

		return $users;
	}

	public function getAllGroups() {
		if ($this->allgroups) return $this->allgroups;

		$this->database->connect();

		$sql = "SELECT g.`id`, g.`name`, g.`info`, g.`archive`
			FROM `base3system_sysgroup` g
			WHERE g.`archive` = 0
			ORDER BY g.`name`";

		$rows = $this->database->multiQuery($sql);
		$this->allgroups = array();
		foreach ($rows as $row) {
			$this->allgroups[] = Group::fromArray($row);
		}

		return $this->allgroups;
	}

	public function getAllRoles() {
		if ($this->allroles) return $this->allroles;

		$this->database->connect();

		$sql = "SELECT r.`id`, r.`name`, r.`label`, r.`info`, r.`archive`
			FROM `base3system_sysrole` r
			WHERE r.`archive` = 0
			ORDER BY r.`name`";

		$rows = $this->database->multiQuery($sql);
		$this->allroles = array();
		foreach ($rows as $row) {
			$this->allroles[] = Role::fromArray($row);
		}

		return $this->allroles;
	}

	public function getAllPermissions() {
		if ($this->allpermissions) return $this->allpermissions;

		$this->database->connect();

		$sql = "SELECT p.`id`, p.`scope`, p.`permission`, p.`label`, p.`info`, p.`archive`
			FROM `base3system_syspermission` p
			WHERE p.`archive` = 0
			ORDER BY p.`scope`, p.`permission`";

		$rows = $this->database->multiQuery($sql);
		$this->allpermissions = array();
		foreach ($rows as $row) {
			$this->allpermissions[] = Permission::fromArray($row);
		}

		return $this->allpermissions;
	}

	public function assignRoleToUser($userid, Role $role): bool {
		$userId = $this->resolveUserId($userid);
		$roleId = $this->resolveRoleId($role);

		if (!$userId || !$roleId) return false;

		$this->database->connect();
		$sql = "INSERT IGNORE INTO `base3system_sysuserrole` (`user_id`, `role_id`)
			VALUES (" . (int)$userId . ", " . (int)$roleId . ")";
		$this->database->nonQuery($sql);
		$this->clearAuthorizationCache();

		return !$this->database->isError();
	}

	public function revokeRoleFromUser($userid, Role $role): bool {
		$userId = $this->resolveUserId($userid);
		$roleId = $this->resolveRoleId($role);

		if (!$userId || !$roleId) return false;

		$this->database->connect();
		$sql = "DELETE FROM `base3system_sysuserrole`
			WHERE `user_id` = " . (int)$userId . "
			  AND `role_id` = " . (int)$roleId;
		$this->database->nonQuery($sql);
		$this->clearAuthorizationCache();

		return !$this->database->isError();
	}

	public function assignRoleToGroup($groupid, Role $role): bool {
		$groupId = $this->resolveGroupId($groupid);
		$roleId = $this->resolveRoleId($role);

		if (!$groupId || !$roleId) return false;

		$this->database->connect();
		$sql = "INSERT IGNORE INTO `base3system_sysgrouprole` (`group_id`, `role_id`)
			VALUES (" . (int)$groupId . ", " . (int)$roleId . ")";
		$this->database->nonQuery($sql);
		$this->clearAuthorizationCache();

		return !$this->database->isError();
	}

	public function revokeRoleFromGroup($groupid, Role $role): bool {
		$groupId = $this->resolveGroupId($groupid);
		$roleId = $this->resolveRoleId($role);

		if (!$groupId || !$roleId) return false;

		$this->database->connect();
		$sql = "DELETE FROM `base3system_sysgrouprole`
			WHERE `group_id` = " . (int)$groupId . "
			  AND `role_id` = " . (int)$roleId;
		$this->database->nonQuery($sql);
		$this->clearAuthorizationCache();

		return !$this->database->isError();
	}

	public function addPermissionToRole(Role $role, Permission $permission): bool {
		$roleId = $this->resolveRoleId($role);
		$permissionId = $this->resolvePermissionId($permission);

		if (!$roleId || !$permissionId) return false;

		$this->database->connect();
		$sql = "INSERT IGNORE INTO `base3system_sysrolepermission` (`role_id`, `permission_id`)
			VALUES (" . (int)$roleId . ", " . (int)$permissionId . ")";
		$this->database->nonQuery($sql);
		$this->clearAuthorizationCache();

		return !$this->database->isError();
	}

	public function removePermissionFromRole(Role $role, Permission $permission): bool {
		$roleId = $this->resolveRoleId($role);
		$permissionId = $this->resolvePermissionId($permission);

		if (!$roleId || !$permissionId) return false;

		$this->database->connect();
		$sql = "DELETE FROM `base3system_sysrolepermission`
			WHERE `role_id` = " . (int)$roleId . "
			  AND `permission_id` = " . (int)$permissionId;
		$this->database->nonQuery($sql);
		$this->clearAuthorizationCache();

		return !$this->database->isError();
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->database == null || $this->accesscontrol == null ? "Fail" : "Ok"
		);
	}

	private function getCurrentUserRow() {
		if ($this->userrow) return $this->userrow;

		$userid = $this->accesscontrol->getUserId();
		if (!$userid || $userid == "internal") return null;

		$this->database->connect();

		$sql = "SELECT u.`id`, u.`name` AS `userid`, u.`fullname`, u.`email`, u.`mode`, l.`short` AS `lang`
			FROM `base3system_sysuser` u
			INNER JOIN `base3system_syslang` l ON u.`lang_id` = l.`id`
			WHERE u.`name` = '" . $this->database->escape((string)$userid) . "'";

		$this->userrow = $this->database->singleQuery($sql);

		return $this->userrow;
	}

	private function getCurrentUserDatabaseId() {
		$row = $this->getCurrentUserRow();
		return $row && isset($row["id"]) ? (int)$row["id"] : null;
	}

	private function getRolesForUserId(int $userId): array {
		$this->database->connect();

		$sql = "SELECT DISTINCT r.`id`, r.`name`, r.`label`, r.`info`, r.`archive`
			FROM `base3system_sysrole` r
			INNER JOIN `base3system_sysuserrole` ur ON ur.`role_id` = r.`id`
			WHERE ur.`user_id` = " . (int)$userId . "
			  AND r.`archive` = 0
			UNION
			SELECT DISTINCT r.`id`, r.`name`, r.`label`, r.`info`, r.`archive`
			FROM `base3system_sysrole` r
			INNER JOIN `base3system_sysgrouprole` gr ON gr.`role_id` = r.`id`
			INNER JOIN `base3system_sysusergroup` ug ON ug.`group_id` = gr.`group_id`
			WHERE ug.`user_id` = " . (int)$userId . "
			  AND r.`archive` = 0
			ORDER BY `name`";

		$rows = $this->database->multiQuery($sql);
		$roles = array();
		foreach ($rows as $row) {
			$roles[] = Role::fromArray($row);
		}

		return $roles;
	}

	private function resolveCompatibilityRole(array $roles, string $fallback): string {
		foreach ($roles as $role) {
			if ($role->name == "admin") return "admin";
		}

		foreach ($roles as $role) {
			if ($role->name == "member") return "member";
		}

		foreach ($roles as $role) {
			if ($role->name == "visit") return "visit";
		}

		return $fallback;
	}

	private function resolveUserId($userid) {
		if ($userid === null || $userid === "") return null;

		$this->database->connect();

		if (is_numeric($userid)) {
			$sql = "SELECT `id` FROM `base3system_sysuser` WHERE `id` = " . (int)$userid;
		} else {
			$sql = "SELECT `id` FROM `base3system_sysuser` WHERE `name` = '" . $this->database->escape((string)$userid) . "'";
		}

		$row = $this->database->singleQuery($sql);
		return $row && isset($row["id"]) ? (int)$row["id"] : null;
	}

	private function resolveGroupId($groupid) {
		if ($groupid === null || $groupid === "") return null;

		$this->database->connect();

		if (is_numeric($groupid)) {
			$sql = "SELECT `id` FROM `base3system_sysgroup` WHERE `id` = " . (int)$groupid;
		} else {
			$sql = "SELECT `id` FROM `base3system_sysgroup` WHERE `name` = '" . $this->database->escape((string)$groupid) . "'";
		}

		$row = $this->database->singleQuery($sql);
		return $row && isset($row["id"]) ? (int)$row["id"] : null;
	}

	private function resolveRoleId(Role $role) {
		if (isset($role->id) && $role->id !== null && $role->id !== "") return (int)$role->id;
		if (!isset($role->name) || !$role->name) return null;

		$this->database->connect();

		$sql = "SELECT `id` FROM `base3system_sysrole`
			WHERE `name` = '" . $this->database->escape((string)$role->name) . "'
			  AND `archive` = 0";

		$row = $this->database->singleQuery($sql);
		return $row && isset($row["id"]) ? (int)$row["id"] : null;
	}

	private function resolvePermissionId(Permission $permission) {
		if (isset($permission->id) && $permission->id !== null && $permission->id !== "") return (int)$permission->id;
		if (!isset($permission->scope, $permission->permission) || !$permission->scope || !$permission->permission) return null;
		if ($this->normalizePermissionTarget($permission) !== null) return null;

		$this->database->connect();

		$sql = "SELECT `id` FROM `base3system_syspermission`
			WHERE `scope` = '" . $this->database->escape((string)$permission->scope) . "'
			  AND `permission` = '" . $this->database->escape((string)$permission->permission) . "'
			  AND `archive` = 0";

		$row = $this->database->singleQuery($sql);
		return $row && isset($row["id"]) ? (int)$row["id"] : null;
	}

	private function isSystemAdminPermission(Permission $permission): bool {
		return (string)$permission->scope === "system"
			&& (string)$permission->permission === "admin"
			&& $this->normalizePermissionTarget($permission) === null;
	}

	private function permissionCovers(Permission $available, Permission $requested): bool {
		if ((string)$available->scope !== (string)$requested->scope) return false;

		$availableTarget = $this->normalizePermissionTarget($available);
		$requestedTarget = $this->normalizePermissionTarget($requested);

		if ($availableTarget !== null && $availableTarget !== $requestedTarget) return false;

		if ((string)$available->permission === (string)$requested->permission) return true;
		if ((string)$available->permission === "admin") return true;

		return false;
	}

	private function normalizePermissionTarget(Permission $permission): ?string {
		if (!isset($permission->target) || $permission->target === null || $permission->target === "") return null;

		return (string)$permission->target;
	}

	private function clearAuthorizationCache(): void {
		$this->roles = null;
		$this->permissions = null;
		$this->allroles = null;
		$this->allpermissions = null;

		if ($this->user) {
			$this->user->roles = $this->getRoles();
			$this->user->role = $this->resolveCompatibilityRole($this->user->roles, $this->user->role ?? "visit");
		}

		if ($this->session && $this->session->started()) {
			unset($_SESSION["authentication"]["roles"]);
			unset($_SESSION["authentication"]["permissions"]);
			if ($this->user) {
				$_SESSION["authentication"]["user"] = $this->user;
			}
		}
	}

}
