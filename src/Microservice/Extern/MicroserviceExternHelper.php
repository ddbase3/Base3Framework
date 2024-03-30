<?php

namespace Microservice\Extern;

use Microservice\Api\IMicroserviceHelper;

class MicroserviceExternHelper implements IMicroserviceHelper {

	private $flags;

	public function __construct($flags = 0) {
		$this->flags = $flags;
	}

	// Implementation of IMicroserviceHelper

	public function get($url, $interface) {

		$m = array();
		$methods = get_class_methods($interface);
		foreach ($methods as $method) {
			if ($method == "__construct") continue;

			$p = array();

			$rm = new \ReflectionMethod($interface, $method);
			$parameters = $rm->getParameters();
			foreach ($parameters as $parameter) $p[] = $parameter->name;
			$m[] = array("name" => $method, "params" => $p);
		}

		$parts = pathinfo($url);
		$service = array("name" => $parts["filename"], "interfaces" => array($interface), "methods" => $m);

		return new MicroserviceExternConnector($url, $service, $this->flags);
	}

}
