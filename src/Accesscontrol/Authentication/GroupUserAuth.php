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

namespace Base3\Accesscontrol\Authentication;

use Base3\Accesscontrol\AbstractAuth;

class GroupUserAuth extends AbstractAuth {

	// Implementation of IBase

	public static function getName(): string {
		return "groupuserauth";
	}

	// Implementation of IAuthentication

	public function login() {
		if (!isset($_REQUEST["password"])) return null;
		$pwfile = DIR_LOCAL . "Authentication" . DIRECTORY_SEPARATOR . "groupusers.json";
		$content = file_get_contents($pwfile);
		$users = json_decode($content, true);
		foreach ($users as $user => $pwhash) {
			if ($pwhash != sha1($_REQUEST["password"])) continue;
			if ($this->verbose) echo "User " . $user . " loaded<br />";
			return $user;
		}
		return null;
	}

}
