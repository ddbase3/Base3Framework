<?php

namespace Status\Session;

use Status\Api\IStatusHandler;
use Api\ICheck;

class SessionStatusHandler implements IStatusHandler, ICheck {

	private $servicelocator;
	private $session;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->session = $this->servicelocator->get('session');
	}

	// Implementation of IStatusHandler

	public function get($fields = null) {
		if ($fields == null) return $_SESSION["status"];
		if (!is_array($fields)) return $_SESSION["status"][$fields];
		$result = array();
		foreach ($fields as $field) $result[$field] = $_SESSION["status"][$field];
		return $result;
	}

	public function set($data) {
		foreach ($data as $key => $value) $_SESSION["status"][$key] = $value;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->session == null ? "Fail" : "Ok"
		);
	}

}
