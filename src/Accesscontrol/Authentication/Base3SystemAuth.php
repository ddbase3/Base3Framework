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

class Base3SystemAuth extends AbstractAuth implements ICheck {

	private $servicelocator;
	private $database;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
	}

	// Implementation of IBase

	public static function getName(): string {
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
