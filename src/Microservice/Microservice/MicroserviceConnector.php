<?php declare(strict_types=1);

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
