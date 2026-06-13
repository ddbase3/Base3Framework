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

use Base3\Core\ServiceLocator;
use Base3\Accesscontrol\AbstractAuth;
use Base3\Api\ICheck;

class CookieAuth extends AbstractAuth implements ICheck {

	private $servicelocator;
	private $classmap;
	private $authtoken;

	private $cookieHashLength;
	private $cookieTimeout;
	private $cookieDomain;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
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

	public static function getName(): string {
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
		setcookie("authentication", json_encode($cookieContent), time() + $this->cookieTimeout, "", $this->cookieDomain ?? '');
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
