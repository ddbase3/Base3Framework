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
use Base3\Api\ICheck;
use Base3\Session\Api\ISession;

class SessionAuth extends AbstractAuth implements ICheck {

	public function __construct(private ISession $session) {}

	// Implementation of IBase

	public static function getName(): string {
		return "sessionauth";
	}

	// Implementation of IAuthentication

	public function login() {
		if (!isset($_SESSION["authentication"]) || !isset($_SESSION["authentication"]["userid"])) return null;
		$userid = $_SESSION["authentication"]["userid"];
		if ($this->verbose) echo "User " . $userid . " loaded<br />";
		return $userid;
	}

	public function keep($userid) {
		if ($userid == null) return;
		if (isset($_SESSION["authentication"]))
			$_SESSION["authentication"]["userid"] = $userid;
		else
			$_SESSION["authentication"] = array("userid" => $userid);
		if ($this->verbose) echo "User " . $userid . " keeped<br />";
	}

	public function logout() {
		if (!isset($_REQUEST["logout"])) return;
		unset($_SESSION["authentication"]);
		if ($this->verbose) echo "User " . $user->getData("id") . " logged out<br />";

/*
// So kann sich CookieAuth nicht ausloggen !!! Daher auskommentiert
		$url = strtok($_SERVER["REQUEST_URI"],'?');
		session_write_close();
		header('Location: ' . $url);
		exit;
*/
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
                        "session_started" => $this->session->started() ? "Ok" : "Fail"
		);
	}

}
