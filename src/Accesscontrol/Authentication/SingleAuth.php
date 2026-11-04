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

class SingleAuth extends AbstractAuth implements ICheck {

	private $servicelocator;
	private $configuration;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->configuration = $this->servicelocator->get('configuration');
	}

	// Implementation of IBase

	public static function getName(): string {
		return "singleauth";
	}

	// Implementation of IAuthentication

	public function login(?array $credentials = null) {
		if ($credentials !== null) return null;
		if ($this->configuration == null) return null;
		$cnf = $this->configuration->get('singleauth');
		if ($cnf == null) return null;
		if (!isset($_REQUEST["login"])) return null;
		if (!isset($_REQUEST["username"]) || !isset($_REQUEST["password"])) return null;
		if ($cnf["user"] != $_REQUEST["username"] || $cnf["pass"] != sha1($_REQUEST["password"])) return null;
		if ($this->verbose) echo "User " . $cnf["user"] . " loaded<br />";
		return $cnf["user"];
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->configuration == null ? "Fail" : "Ok"
		);
	}
}
