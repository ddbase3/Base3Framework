<?php declare(strict_types=1);

namespace Base3\Accesscontrol\Authentication;

use Base3\Core\ServiceLocator;
use Base3\Accesscontrol\AbstractAuth;
use Base3\Api\ICheck;

class SingleAuth extends AbstractAuth implements ICheck {

	private $servicelocator;
	private $configuration;

	private $cnf;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->configuration = $this->servicelocator->get('configuration');

		if ($this->configuration != null) $this->cnf = $this->configuration->get('singleauth');
	}

	// Implementation of IBase

	public static function getName(): string {
		return "singleauth";
	}

	// Implementation of IAuthentication

	public function login() {
		if ($this->cnf == null) return null;
		if (!isset($_REQUEST["login"])) return null;
		if (!isset($_REQUEST["username"]) || !isset($_REQUEST["password"])) return null;
		if ($this->cnf["user"] != $_REQUEST["username"] || $this->cnf["pass"] != sha1($_REQUEST["password"])) return null;
		if ($this->verbose) echo "User " . $this->cnf["user"] . " loaded<br />";
		return $this->cnf["user"];
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->configuration == null ? "Fail" : "Ok"
		);
	}

}
