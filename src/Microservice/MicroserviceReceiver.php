<?php declare(strict_types=1);

namespace Base3\Microservice;

use Base3\Core\ServiceLocator;
use Base3\Microservice\Api\IMicroserviceReceiver;

class MicroserviceReceiver extends AbstractMicroservice implements IMicroserviceReceiver {

	private $servicelocator;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
	}

	// Implementation of IBase

	public static function getName(): string {
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
