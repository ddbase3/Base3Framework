<?php declare(strict_types=1);

namespace Base3\Usermanager;

use Base3\Usermanager\Api\IUsermanager;

class UsermanagerProxy implements IUsermanager {

	private $connector;

	public function __construct($connector) {
		$this->connector = $connector;
	}

	public function getUser() {
		return $this->connector->getUser();
	}

	public function getGroups() {
		return $this->connector->getGroups();
	}

	public function registUser($userid, $password, $data = null) {
		return $this->connector->registUser($userid, $password, $data);
	}

	public function changePassword($oldpassword, $newpassword) {
		return $this->connector->changePassword($oldpassword, $newpassword);
	}

	public function getAllUsers() {
		return $this->connector->getAllUsers();
	}
}
