<?php declare(strict_types=1);

namespace Base3\Usermanager;

use Base3\Usermanager\Api\IUsermanager;
use Base3\Usermanager\User;

class UsermanagerProxy implements IUsermanager {

	private $connector;

	public function __construct($connector) {
		$this->connector = $connector;
	}

	public function getUser() {
		$user = $this->connector->getUser();
		return is_array($user) ? User::fromArray($user) : $user;
	}

	public function getGroups() {
		$groups = $this->connector->getGroups();

		if (is_array($groups) && isset($groups[0]) && is_array($groups[0])) {
			return array_map(fn($g) => User::fromArray($g), $groups);
		}

		return $groups;
	}

	public function registUser($userid, $password, $data = null) {
		return $this->connector->registUser($userid, $password, $data);
	}

	public function changePassword($oldpassword, $newpassword) {
		return $this->connector->changePassword($oldpassword, $newpassword);
	}

	public function getAllUsers() {
		$users = $this->connector->getAllUsers();

		if (is_array($users) && isset($users[0]) && is_array($users[0])) {
			return array_map(fn($u) => User::fromArray($u), $users);
		}

		return $users;
	}
}
