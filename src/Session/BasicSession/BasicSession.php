<?php declare(strict_types=1);

namespace Base3\Session\BasicSession;

use Base3\Core\ServiceLocator;
use Base3\Session\Api\ISession;
use Base3\Api\ICheck;

class BasicSession implements ISession, ICheck {

	private $servicelocator;

	private $started;

	public function __construct($cnf = null) {

		$this->servicelocator = ServiceLocator::getInstance();

		$this->started = false;

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

	public function started(): bool {
		return $this->started;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->servicelocator->get('configuration') == null ? "Fail" : "Ok"
		);
	}

}
