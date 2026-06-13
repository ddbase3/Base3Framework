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

class MultiAuth extends AbstractAuth implements ICheck {

	private $servicelocator;
	private $configuration;

	private $cnf;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->configuration = $this->servicelocator->get('configuration');

		if ($this->configuration != null) $this->cnf = $this->configuration->get('multiauth');
	}

	// Implementation of IBase

	public static function getName(): string {
		return "multiauth";
	}

	// Implementation of IAuthentication

	public function login() {
		if ($this->cnf == null) return null;
		if (!isset($_REQUEST["login"])) return null;
		if (!isset($_REQUEST["username"]) || !isset($_REQUEST["password"])) return null;
		foreach ($this->cnf["user"] as $key => $user) {
			if ($user != $_REQUEST["username"] || $this->cnf["pass"][$key] != sha1($_REQUEST["password"])) continue;
			if ($this->verbose) echo "User " . $user . " loaded<br />";
			return $user;
		}
		return null;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->configuration == null ? "Fail" : "Ok"
		);
	}

}
