<?php

namespace Accesscontrol\Authentication;

use Accesscontrol\AbstractAuth;
use Api\ICheck;

class CookieAuth extends AbstractAuth implements ICheck {

	private $servicelocator;
	private $classmap;
	private $authtoken;

	private $cookieHashLength;
	private $cookieTimeout;
	private $cookieDomain;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->classmap = $this->servicelocator->get('classmap');
		$this->authtoken = $this->servicelocator->get('authtoken');

		$this->cookieHashLength = 32;
		$this->cookieTimeout = 3600 * 24 * 7;

		/*
		// TODO konfigurieren, ob auf Hauptdomain oder nicht
		if (isset($_SERVER["SERVER_NAME"])) {
			$domparts = explode(".", $_SERVER["SERVER_NAME"]);
			if (sizeof($domparts) >= 2) $this->cookieDomain = $domparts[sizeof($domparts) - 2] . "." . $domparts[sizeof($domparts) - 1];
		}
		*/
	}

	// Implementation of IBase

	public function getName() {
		return "cookieauth";
	}

	// Implementation of IAuthentication

	public function login() {
		if (!isset($_COOKIE["authentication"])) return null;
		$cookieContent = json_decode($_COOKIE["authentication"], true);
		$userid = $cookieContent["userid"];
		if (!$this->authtoken->check("authentication", $userid, $cookieContent["token"])) return null;
		if ($this->verbose) echo "User " . $userid . " loaded<br />";
		return $userid;
	}

	public function keep($userid) {
		if ($userid == null) return;
		$this->clean();
		$cookieContent = array(
			"userid" => $userid,
			"token" => $this->authtoken->create("authentication", $userid, $this->cookieHashLength, $this->cookieTimeout)
		);
		// TODO see additional parameters for more security (https://www.w3schools.com/php/func_http_setcookie.asp)
		setcookie("authentication", json_encode($cookieContent), time() + $this->cookieTimeout, "", $this->cookieDomain);
		if ($this->verbose) echo "User " . $userid . " keeped<br />";
	}

	public function logout() {
		if (!isset($_REQUEST["logout"])) return;
		$this->clean();
		setcookie("authentication", "", time() - 3600 * 24, "", $this->cookieDomain);
		unset($_COOKIE["authentication"]);
	}

	private function clean() {
		if (!isset($_COOKIE["authentication"])) return;
		$cookieContent = json_decode($_COOKIE["authentication"], true);
		if ($cookieContent["userid"]) $this->authtoken->delete("authentication", $cookieContent["userid"], $cookieContent["token"]);
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->classmap == null || $this->authtoken == null ? "Fail" : "Ok"
		);
	}

}
