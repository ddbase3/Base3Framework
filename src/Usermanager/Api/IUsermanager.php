<?php

namespace Usermanager\Api;

interface IUsermanager {

	public function getUser();
	public function getGroups();
	public function registUser($userid, $password, $data = null);
	public function changePassword($oldpassword, $newpassword);
	public function getAllUsers();

}
