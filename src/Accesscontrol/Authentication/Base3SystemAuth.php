<?php

namespace Accesscontrol\Authentication;

use Accesscontrol\AbstractAuth;
use Api\ICheck;

class Base3SystemAuth extends AbstractAuth implements ICheck {

	private $servicelocator;
	private $database;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
	}

	// Implementation of IBase

	public function getName() {
		return "base3systemauth";
	}

	// Implementation of IAuthentication

	public function login() {
		if ($this->database == null) return null;
		if (!isset($_REQUEST["login"])) return null;
		if (!isset($_REQUEST["username"]) || !isset($_REQUEST["password"])) return null;

		$this->database->connect();
		$sql = "SELECT `name`, `mode`
			FROM `base3system_sysuser`
			WHERE `name` = '" . $this->database->escape($_REQUEST["username"]) . "' AND `password` = '" . md5($_REQUEST["password"]) . "' LIMIT 1";
		$row = $this->database->singleQuery($sql);
		if ($row == null) return null;

		// special login (bypass)
		if (isset($_REQUEST["switchuser"]) && $row["mode"] == 2) {
			if ($this->verbose) echo "User " . $_REQUEST["switchuser"] . " loaded<br />";
			return $_REQUEST["switchuser"];
		}

		if ($this->verbose) echo "User " . $row["name"] . " loaded<br />";
		return $row["name"];
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->database == null ? "Fail" : "Ok"
		);
	}

}
