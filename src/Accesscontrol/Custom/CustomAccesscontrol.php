<?php

namespace Accesscontrol\Custom;

use Accesscontrol\Api\IAccesscontrol;
use Api\ICheck;

class CustomAccesscontrol implements IAccesscontrol, ICheck {

	private $servicelocator;
	private $classmap;

	private $userid = null;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->classmap = $this->servicelocator->get('classmap');

		$verbose = isset($_REQUEST["checkaccesscontrol"]);

		$authentications = $this->classmap->getInstancesByInterface("Accesscontrol\\Api\\IAuthentication");
		foreach ($authentications as $authentication) $authentication->setVerbose($verbose);

		if ($verbose) echo "=================================<br />LOGOUT<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . get_class($authentication) . "<br />";
			$authentication->logout();
		}

		if ($verbose) echo "=================================<br />LOGIN<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . get_class($authentication) . "<br />";
			$userid = $authentication->login();
			if ($userid != null) $this->userid = $userid;
			if ($verbose) echo "&bullet; user: " . ($userid == null ? "null" : $userid) . "<br />";
		}

		if ($verbose) echo "=================================<br />KEEP<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . get_class($authentication) . "<br />";
			// TODO check isKeepable ???
			if ($this->userid != null) $authentication->keep($this->userid);
		}

		if ($verbose) echo "=================================<br />FINISH<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . get_class($authentication) . "<br />";
			$authentication->finish($this->userid);
		}

		if ($verbose) exit;
	}

	// Implementation of IAccesscontrol

	public function getUserId() {
		return $this->userid;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->classmap == null ? "Fail" : "Ok"
		);
	}

}
