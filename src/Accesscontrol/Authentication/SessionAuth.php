<?php

namespace Accesscontrol\Authentication;

use Accesscontrol\AbstractAuth;
use Api\ICheck;

class SessionAuth extends AbstractAuth implements ICheck {

	private $servicelocator;
	private $session;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->session = $this->servicelocator->get('session');
	}

	// Implementation of IBase

	public function getName() {
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
			"depending_services" => $this->session == null ? "Fail" : "Ok"
		);
	}

}
