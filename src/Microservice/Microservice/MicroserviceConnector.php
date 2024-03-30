<?php

namespace Microservice\Microservice;

use Microservice\AbstractMicroserviceConnector;
use Api\ICheck;

class MicroserviceConnector extends AbstractMicroserviceConnector implements ICheck {

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"microservice_masterpass_defined" => isset($this->cnf['masterpass']) ? "Ok" : "masterpass not defined",
			"microservice_masterpass_length" => isset($this->cnf['masterpass']) && strlen($this->cnf['masterpass']) >= 32 ? "Ok" : "masterpass to short",
			"service_available" => $this->service["name"] == json_decode($this->httpPost($this->url, array("call" => "getName", "params" => array()))) ? "Ok" : "Failed to call service " . $this->getMicroserviceUrl()
		);
	}

}
