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

namespace Base3\Microservice\Microservice;

use Base3\Microservice\AbstractMicroserviceConnector;
use Base3\Api\ICheck;

class MicroserviceConnector extends AbstractMicroserviceConnector implements ICheck {

	// Implementation of ICheck

	public function checkDependencies() {

		$testCallResult = $this->httpPost($this->url, array("call" => "getName", "params" => array()));

		return array(
			"microservice_masterpass_defined" => isset($this->cnf['masterpass']) ? "Ok" : "masterpass not defined",
			"microservice_masterpass_length" => isset($this->cnf['masterpass']) && strlen($this->cnf['masterpass']) >= 32 ? "Ok" : "masterpass to short",
			"service_available" => $testCallResult && $this->service["name"] == json_decode($testCallResult) ? "Ok" : "Failed to call service " . $this->getMicroserviceUrl()
		);
	}

}
