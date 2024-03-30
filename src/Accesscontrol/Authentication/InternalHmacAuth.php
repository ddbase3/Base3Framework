<?php

namespace Accesscontrol\Authentication;

use Accesscontrol\AbstractAuth;
use Api\ICheck;

class InternalHmacAuth extends AbstractAuth implements ICheck {

	private $servicelocator;
	private $classmap;
	private $configuration;
	private $authtoken;

	private $data;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->classmap = $this->servicelocator->get('classmap');
		$this->configuration = $this->servicelocator->get('configuration');
		$this->authtoken = $this->servicelocator->get('authtoken');

		if ($this->configuration != null) {
			$cnf = $this->configuration->get('microservice');
			if ($cnf != null) {
				$this->data["id"] = "internal";
				$this->data["username"] = "internal";
				$this->data["password"] = $cnf["masterpass"];
			}
		}
	}

	// Implementation of IBase

	public function getName() {
		return "internalhmacauth";
	}

	// Implementation of IAuthentication

	public function login() {
		// Anmeldung wird nicht gehalten/Rest-Service ist stateless, also kein keep/logout

		if (!isset($_SERVER["HTTP_USER"]) || !isset($_SERVER["HTTP_TIME"]) || !isset($_SERVER["HTTP_TOKEN"]) || !isset($_SERVER["HTTP_HASH"])) return null;

		$timeout = 3*60*60;  // 3 hours
		$timeoutsavetokens = 12*60*60;  // 12 hours
		$tokenscope = $this->getName();
		$tokenid = $this->data["username"];

		// timeout block
		if ($_SERVER["HTTP_TIME"] < time() - $timeout) return null;  // request timed out

		// no reuse of token
		if ($this->authtoken->check($tokenscope, $tokenid, $_SERVER["HTTP_TOKEN"])) return null;  // token already used
		$this->authtoken->save($tokenscope, $tokenid, $_SERVER["HTTP_TOKEN"], $timeoutsavetokens);

		// username block
		if ($_SERVER["HTTP_USER"] != $this->data["username"]) return null;  // unknown user name

		// hash block
		$hash_check = sha1($this->data["password"] . $_SERVER["HTTP_TIME"] . $_SERVER["HTTP_TOKEN"]);
		if ($_SERVER["HTTP_HASH"] != $hash_check) return null;  // wrong hash

		// representing a logged in user?
		if (isset($_SERVER["HTTP_AUTH"])) {
			if ($this->verbose) echo "User " .$_SERVER["HTTP_AUTH"] . " loaded<br />";
			return $_SERVER["HTTP_AUTH"];
		}

/*
		if ($this->verbose) echo "User " . $this->data["id"] . " loaded<br />";
		return $this->data["id"];
*/
		if ($this->verbose) echo "No user loaded<br />";
		return null;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->classmap == null || $this->configuration == null || $this->authtoken == null ? "Fail" : "Ok"
		);
	}

}
