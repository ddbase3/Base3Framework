<?php declare(strict_types=1);

namespace Base3\Usermanager\No;

use Base3\Usermanager\Api\IUsermanager;

class NoUsermanager implements IUsermanager {

	// Implementation of IUsermanager

	public function getUser() {
		return null;
	}

	public function getGroups() {
		return array();
	}

	public function registUser($userid, $password, $data = null) {
	}

	public function changePassword($oldpassword, $newpassword) {
	}

	public function getAllUsers() {
		return array();
	}
}
