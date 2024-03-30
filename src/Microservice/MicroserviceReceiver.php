<?php

namespace Microservice;

use Microservice\Api\IMicroserviceReceiver;

class MicroserviceReceiver extends AbstractMicroservice implements IMicroserviceReceiver {

	private $servicelocator;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
	}

	// Implementation of IBase

	public function getName() {
		return "microservicereceiver";
	}

	// Implementation of IMicroserviceReceiver

	public function ping() {
		return "pong";
	}

	public function connect($services) {

		// never instantiate in constructor because of endless recursion
		$microservicehelper = $this->servicelocator->get('microservicehelper');

		$response = $microservicehelper->set($services);
		return $response;
	}

	// Implementation of IOutput

	public function getHelp() {
		return 'Help for MicroserviceReceiver';
	}

}
