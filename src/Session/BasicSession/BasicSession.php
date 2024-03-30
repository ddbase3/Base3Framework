<?php

namespace Session\BasicSession;

use Session\Api\ISession;
use Api\ICheck;

class BasicSession implements ISession, ICheck {

	private $servicelocator;

	private $started;

	public function __construct($cnf = null) {

		$this->servicelocator = \Base3\ServiceLocator::getInstance();

		if ($cnf == null) {
			$configuration = $this->servicelocator->get('configuration');
			$cnf = $configuration == null
				? array("extensions" => array(), "cookiedomain" => "")
				: $configuration->get('session');
		}

		// only create session, if chosen output is one of the session extensions
		if (!isset($_REQUEST['out']) || !in_array($_REQUEST['out'], $cnf["extensions"])) return;
		session_start();
		$this->started = true;
	}

	public function started() {
		return $this->started;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->servicelocator->get('configuration') == null ? "Fail" : "Ok"
		);
	}

}
