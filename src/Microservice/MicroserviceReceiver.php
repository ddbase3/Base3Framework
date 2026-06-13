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

	public function getHelp(): string {
		return 'Help for MicroserviceReceiver';
	}
}
