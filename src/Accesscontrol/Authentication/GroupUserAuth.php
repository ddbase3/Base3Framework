<?php

namespace Accesscontrol\Authentication;

use Accesscontrol\AbstractAuth;

class GroupUserAuth extends AbstractAuth {

	// Implementation of IBase

	public function getName() {
		return "groupuserauth";
	}

	// Implementation of IAuthentication

	public function login() {
		if (!isset($_REQUEST["password"])) return null;
		$pwfile = DIR_LOCAL . "Authentication" . DIRECTORY_SEPARATOR . "groupusers.json";
		$content = file_get_contents($pwfile);
		$users = json_decode($content, 1);
		foreach ($users as $user => $pwhash) {
			if ($pwhash != sha1($_REQUEST["password"])) continue;
			if ($this->verbose) echo "User " . $user . " loaded<br />";
			return $user;
		}
		return null;
	}

}
