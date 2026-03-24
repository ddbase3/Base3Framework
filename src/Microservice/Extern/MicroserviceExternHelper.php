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

namespace Base3\Microservice\Extern;

use Base3\Microservice\Api\IMicroserviceHelper;

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
