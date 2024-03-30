<?php

namespace Accesscontrol\Selected;

use Accesscontrol\Api\IAccesscontrol;
use Api\ICheck;

class SelectedAccesscontrol implements IAccesscontrol, ICheck {

	private $servicelocator;
	private $authentications;

	private $userid = null;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->authentications = $this->servicelocator->get('authentications');

		$verbose = isset($_REQUEST["checkaccesscontrol"]);

		$authentications = array();
		foreach ($this->authentications as $authentication) $authentications[] = $authentication();

		foreach ($authentications as $authentication) $authentication->setVerbose($verbose);

		if ($verbose) echo "=================================<br />LOGOUT<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . $authentication->getName() . "<br />";
			$authentication->logout();
		}

		if ($verbose) echo "=================================<br />LOGIN<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . $authentication->getName() . "<br />";
			$userid = $authentication->login();
			if ($userid != null) $this->userid = $userid;
			if ($verbose) echo "&bullet; user: " . ($userid == null ? "null" : $userid) . "<br />";
		}

		if ($verbose) echo "=================================<br />KEEP<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . $authentication->getName() . "<br />";
			// TODO check isKeepable ???
			if ($this->userid != null) $authentication->keep($this->userid);
		}

		if ($verbose) echo "=================================<br />FINISH<br />";
		foreach ($authentications as $authentication) {
			if ($verbose) echo "---------------------------------<br />" . $authentication->getName() . "<br />";
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
			"depending_services" => $this->authentications == null ? "Fail" : "Ok"
		);
	}

}
